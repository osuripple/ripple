<?php
/*
 * Osu! direct search
*/
require_once '../inc/functions.php';
try {
	// Check if everything is set
	if (!isset($_GET['u']) || !isset($_GET['h']) || empty($_GET['u']) || empty($_GET['h'])) {
		throw new Exception();
	}
	// Check login
	if (!PasswordHelper::CheckPass($_GET['u'], $_GET['h'])) {
		throw new Exception();
	}
	// Make sure mode and ranked status are set
	if (!isset($_GET['m']) || !isset($_GET['r'])) {
		throw new Exception();
	}
	// Default values for bloodcat query
	$bcM = '0';
	$bcS = '1,2,3,0';
	$bcQ = '';
	$bcPopular = false;
	$bcP = 1;
	// Modes
	if ($_GET['m'] == -1) {
		$bcM = '0,1,2,3';
	} // All
	else {
		$bcM = $_GET['m'];
	} // Specific mode
	// Ranked status
	// Bloodcat and osu! use differend
	// ranked status ids for beatmap
	switch ($_GET['r']) {
			// Ranked/Ranked played (Ranked)

		case 0:
		case 7:
			$bcS = '1';
		break;
			// Qualified (Qualified)

		case 3:
			$bcS = '3';
		break;
			// Pending/Help (Approved)

		case 2:
			$bcS = '2';
		break;
			// Graveyard (Unranked)

		case 5:
			$bcS = '0';
		break;
			// All

		case 4:
			$bcS = '1,2,3,0';
		break;
	}
	// Search query
	// To search for Top rated, most played and newest beatmaps,
	// osu! sends a specific query to osu! direct search script.
	// Bloodcat uses a popular.php file instead to show all popular maps
	// If we have selected top rated/most played, we'll fetch popular.php's content
	// If we have selected newest, we'll fetch index.php content with no search query
	// Otherwise, we've searched for a specific map, so we pass the search query
	// to bloodcat
	if (isset($_GET['q']) && !empty($_GET['q'])) {
		if ($_GET['q'] == 'Top Rated' || $_GET['q'] == 'Most Played') {
			$bcPopular = true;
		} elseif ($_GET['q'] == 'Newest') {
			$bcQ = '';
		} else {
			$bcQ = $_GET['q'];
		}
	} else {
		$bcQ = '';
	}
	// Page
	// Osu's first page is 0
	// Bloodcat's first page is 1
	if (isset($_GET['p'])) {
		$bcP = $_GET['p'] + 1;
	}
	// Replace spaces with + in query
	$bcQ = str_replace(' ', '+', $bcQ);
	// Build the URL with popular.php or normal bloodcat API
	$bcURL = $bcPopular ? 'http://bloodcat.com/osu/popular.php?mod=json&m='.$bcM.'&p='.$bcP : 'http://bloodcat.com/osu/?mod=json&m='.$bcM.'&s='.$bcS.'&q='.$bcQ.'&p='.$bcP;
	// Get API response and save it in an array
	$bcData = json_decode(file_get_contents($bcURL), true);
	// Output variable
	$output = '';
	// Show 101 if we have >= 40 results (bloodcat maps per page)
	// or osu! won't load next pages
	if (count($bcData) >= 40) {
		$output = 101;
	} else {
		$output = count($bcData);
	}
	// Separator
	$output .= "\r\n";
	// Add to output beatmap info for each song
	foreach ($bcData as $song) {
		$output .= bloodcatDirectString($song)."\r\n";
	}
	// Done, output everything
	echo $output;
	// bmapid.osz|Artist|Song name|mapper|ranked(1/0)|idk(prob star rating)|last update|bmap id again|topic id tho|has video(0/1)|0|0||Diff 1@mode,Diff 2@mode

}
catch(Exception $e) {
	echo $e->getMessage();
}
