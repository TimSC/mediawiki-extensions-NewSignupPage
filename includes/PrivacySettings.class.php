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
		if($action == "optin")
		{
			$user->addGroup( "TOS Accepted" );
			$user->removeGroup( "TOS Rejected" );
			$tos_accepted = true;
		}
		elseif ($action == "optout")
		{
			$user->removeGroup( "TOS Accepted" );
			$user->addGroup( "TOS Rejected" );
			$tos_accepted = false;
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
}
