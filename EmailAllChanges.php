<?php
/**
 * EmailAllChanges MediaWiki extension.
 *
 * This extension adds a preferences checkbox allowing users to have all
 * changes to pages on the wiki emailed to them.
 *
 * Written by Leucosticte
 * http://www.mediawiki.org/wiki/User:Leucosticte
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */

# Alert the user that this is not a valid entry point to MediaWiki if the user tries to access the
# extension file directly.
if( !defined('MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension. It is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'EmailAllChanges',
	'author' => 'Nathan Larson',
	'url' => 'https://www.mediawiki.org/wiki/Extension:EmailAllChanges',
	'descriptionmsg' => 'emailallchanges-desc',
	'version' => '1.0.2',
);

$wgHooks['GetPreferences'][] = 'EmailAllChangesTogglify';
$wgHooks['AbortEmailNotification'][] = 'EmailAllChangesOnAbortEmailNotification';
$wgExtensionMessagesFiles['EmailAllChanges'] = __DIR__ . '/EmailAllChanges.i18n.php';
$wgEmailAllChangesRight = 'block';
$wgEmailAllChangesExcludePages = array( 'MediaWiki:InterwikiMapBackup' );

function EmailAllChangesTogglify( $user, &$preferences )  {
	global $wgEmailAllChangesRight;
	if( !in_array ( $wgEmailAllChangesRight, $user->getRights() ) ) {
		return true;
	}
	// A checkbox
	$preferences['emailallchanges'] = array(
		'type' => 'toggle',
		'label-message' => 'tog-emailallchanges', // a system message
		'section' => 'personal/email',
	);
	return true;
}

function EmailAllChangesOnAbortEmailNotification ( $editor, $title ) {
	// TODO: Come up with some way of weeding out ancient accounts whose users have long since
	// quit editing. E.g. query the recentchanges and/or revision table to find out when the
	// user last edited.
	global $wgUsersNotifiedOnAllChanges, $wgEmailAllChangesRight, $wgEmailAllChangesExcludePages;
	if ( in_array( getPrefixedDBKey( $title ), $wgEmailAllChangesExcludePages ) ) {
		return true;
	}
	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select( 'user_properties', 'up_user', array(
		'up_property' => 'emailallchanges',
		'up_value' => '1'
		) );
	$userIDs = array();
	foreach( $res as $row ) {
		$userIDs[] = intval( $row->up_user );
	}
	$users = UserArray::newFromIDs( $userIDs );
	$userNames = array();
	foreach ( $users as $user ) {
		$userNames[] = $user->getName();
	}
	$wgUsersNotifiedOnAllChanges = array_unique( array_merge( $userNames,
		$wgUsersNotifiedOnAllChanges ) );
	return true;
}
