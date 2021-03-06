<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('gallery');

$images = \OCP\Files::searchByMime('image');
$user = \OCP\User::getUser();

foreach ($images as &$image) {
	$path = $user . $image['path'];
	if(strpos($path, DIRECTORY_SEPARATOR.".")){
		continue;
	}
	$image['path'] = $user . $image['path'];
}

$shared = array();
$sharedSources = OCP\Share::getItemsSharedWith('file');
$users = array();
foreach ($sharedSources as $sharedSource) {
	$owner = $sharedSource['uid_owner'];
	if (array_search($owner, $users) === false) {
		$users[] = $owner;
	}
	\OC\Files\Filesystem::initMountPoints($owner);
	$ownerView = new \OC\Files\View('/' . $owner . '/files');
	$path = $ownerView->getPath($sharedSource['item_source']);
	if ($path) {
		$shareName = basename($path);
		$shareView = new \OC\Files\View('/' . $owner . '/files' . $path);
		$sharedImages = $shareView->searchByMime('image');
		foreach ($sharedImages as $sharedImage) {
			$sharedImage['path'] = $owner . $sharedSource['file_target'] . '/' . $shareName . $sharedImage['path'];
			$images[] = $sharedImage;
		}
	}
}

$displayNames = array();
foreach ($users as $user) {
	$displayNames[$user] = \OCP\User::getDisplayName($user);
}

function startsWith($haystack, $needle)
{
	return !strncmp($haystack, $needle, strlen($needle));
}

OCP\JSON::setContentTypeHeader();
echo json_encode(array('images' => $images, 'users' => $users, 'displayNames' => $displayNames));
