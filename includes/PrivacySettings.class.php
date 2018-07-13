<?php

class PrivacySettingsExt extends SpecialPage {
	function __construct() {
		parent::__construct( "privacy-settings" );
	}

	function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();
		$this->setHeaders();

		# Get request data from, e.g.
		$action = $request->getText( 'action_code' );
		if($user->isAnon())
		{
			$tos_accepted = false;
		}
		elseif($action == "optin")
		{
			$oldGroups = $user->getGroups();
			$oldUGMs = $user->getGroupMemberships();

			$user->addGroup( "TOS Accepted" );
			$user->removeGroup( "TOS Rejected" );
			$tos_accepted = true;

			$newGroups = $user->getGroups();
			$newUGMs = $user->getGroupMemberships();

			self::addLogEntry( $user, $oldGroups, $newGroups, 
				"User accepted terms of service", array(), $oldUGMs, $newUGMs);

		}
		elseif ($action == "optout")
		{
			$oldGroups = $user->getGroups();
			$oldUGMs = $user->getGroupMemberships();

			$user->removeGroup( "TOS Accepted" );
			$user->addGroup( "TOS Rejected" );
			$tos_accepted = false;

			$newGroups = $user->getGroups();
			$newUGMs = $user->getGroupMemberships();

			self::addLogEntry( $user, $oldGroups, $newGroups, 
				"User rejected terms of service", array(), $oldUGMs, $newUGMs);
		}
		else	
			$tos_accepted = in_array("TOS Accepted", $user->getGroups());

		$output->addHtml( "<p>".wfMessage( "privacy-settings-review" )->parse()."</p>" );

		$output->addHtml("<form method='post'>");
		if($tos_accepted)
		{
			$output->addHtml("<input type='hidden' name='action_code' value='optout'>");
			$output->addHtml("<input type='submit' name='action' value='Opt out'>");
		}
		else
		{
			$output->addHtml("<input type='hidden' name='action_code' value='optin'>");
			$output->addHtml("<input type='submit' name='action' value='Opt in'>");
		}
		$output->addHtml("</form>");
	}

    function getGroupName() {
		return 'users';
    }

/**
	 * Add a rights log entry for an action.
	 * @param User|UserRightsProxy $user
	 * @param array $oldGroups
	 * @param array $newGroups
	 * @param array $reason
	 * @param array $tags Change tags for the log entry
	 * @param array $oldUGMs Associative array of (group name => UserGroupMembership)
	 * @param array $newUGMs Associative array of (group name => UserGroupMembership)
	 */

	public static function addLogEntry( $user, $oldGroups, $newGroups, $reason, $tags,
		$oldUGMs, $newUGMs
	) {
		// make sure $oldUGMs and $newUGMs are in the same order, and serialise
		// each UGM object to a simplified array
		$oldUGMs = array_map( function ( $group ) use ( $oldUGMs ) {
			return isset( $oldUGMs[$group] ) ?
				self::serialiseUgmForLog( $oldUGMs[$group] ) :
				null;
		}, $oldGroups );
		$newUGMs = array_map( function ( $group ) use ( $newUGMs ) {
			return isset( $newUGMs[$group] ) ?
				self::serialiseUgmForLog( $newUGMs[$group] ) :
				null;
		}, $newGroups );

		$logEntry = new ManualLogEntry( 'rights', 'rights' );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( $user->getUserPage() );
		$logEntry->setComment( $reason );
		$logEntry->setParameters( [
			'4::oldgroups' => $oldGroups,
			'5::newgroups' => $newGroups,
			'oldmetadata' => $oldUGMs,
			'newmetadata' => $newUGMs,
		] );
		$logid = $logEntry->insert();
		if ( count( $tags ) ) {
			$logEntry->setTags( $tags );
		}
		$logEntry->publish( $logid );
	}

	/**
	 * Serialise a UserGroupMembership object for storage in the log_params section
	 * of the logging table. Only keeps essential data, removing redundant fields.
	 *
	 * @param UserGroupMembership|null $ugm May be null if things get borked
	 * @return array
	 */
	protected static function serialiseUgmForLog( $ugm ) {
		if ( !$ugm instanceof UserGroupMembership ) {
			return null;
		}
		return [ 'expiry' => $ugm->getExpiry() ];
	}
}

