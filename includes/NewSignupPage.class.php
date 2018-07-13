<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * All class methods are public and static.
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @copyright Copyright © 2008-2017 Jack Phoenix
 * @license GPL-2.0-or-later
 */
class NewSignupPage {

	/**
	 * Add the JavaScript file to the page output on the signup page.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		$context = $out;
		$title = $context->getTitle();
		$request = $context->getRequest();
		$user = $context->getUser();

		// Only do our magic if we're on the signup page
		if ( $title->isSpecial( 'CreateAccount' ) ) {
			// It's called Special:CreateAccount since AuthManager (MW 1.27+)
			$out->addModules( 'ext.newsignuppage' );
		} elseif ( $title->isSpecial( 'Userlogin' ) ) {
			$kaboom = explode( '/', $title->getText() );
			$signupParamIsSet = false;

			// Catch [[Special:UserLogin/signup]]
			if ( isset( $kaboom[1] ) && $kaboom[1] == 'signup' ) {
				$signupParamIsSet = true;
			}

			// Both index.php?title=Special:UserLogin&type=signup and
			// Special:UserLogin/signup are valid, obviously
			if (
				$request->getVal( 'type' ) == 'signup' ||
				$signupParamIsSet
			) {
				$out->addModules( 'ext.newsignuppage' );
			}
		}

		return true;
	}

	static function onEditPageBeforeEditButtons( $editpage, $buttons, $tabindex ) {

		global $wgNspForceAnonEditExplicitAccept;
		if(!$wgNspForceAnonEditExplicitAccept) return;

		$context = $editpage->getArticle()->getContext();
		$user = $context->getUser();
		if($user->isAnon())
		{
			$out = $context->getOutput();
			$out->addHTML( "<input type=\"checkbox\" name=\"accept-tos\"> ".wfMessage( "shoutwiki-anonedit-tos" )->parse()."<br>" );
		}
	}

	static function onEditFilter( $editor, $text, $section, &$error, $summary )
	{
		global $wgNspForceAnonEditExplicitAccept;
		if(!$wgNspForceAnonEditExplicitAccept) return true;

		$context = $editor->getArticle()->getContext();
		$request = $context->getRequest();
		$user = $context->getUser();
		if($user->isAnon() and !$request->getBool('accept-tos'))
		{
			$error = "{{warning|".wfMessage( "shoutwiki-anonedit-must-accept-tos" )->parse()."}}";

			return true;
		}
		return true;
	}

	static function onLocalUserCreated( $user, $autocreated )
	{
		global $wgNspExplicitAddToAcceptGroup;
		if(is_string($wgNspExplicitAddToAcceptGroup) and !$autocreated)
		{
			// https://stackoverflow.com/a/39891374
			$user->addGroup( "TOS Accepted" );
		}
	}
	
	static function onArticleViewHeader( &$article, &$outputDone, &$pcache )
	{
		$context = $article->getContext();
		$out = $context->getOutput();
		$user = $context->getUser();
		if(!$user->isAnon() and !in_array("TOS Accepted", $user->getGroups()))
			$out->addWikitext( "{{warning|".wfMessage( "privacy-settings-remind-existing-user" )->parse()."}}" );
	}

}

