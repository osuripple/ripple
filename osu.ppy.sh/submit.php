<?php
	/*
	 * Form submission php file
	 */

	require_once("./inc/functions.php");

	try
	{
		// Find what the user wants to do (compatible with both GET/POST forms)
		if (isset($_POST["action"]) && !empty($_POST["action"]))
			$action = $_POST["action"];
		else if (isset($_GET["action"]) && !empty($_GET["action"]))
			$action = $_GET["action"];
		else throw new Exception("Couldn't find action parameter");

		foreach ($pages as $page) {
			if ($action == $page::URL) {
				if (isset($page->mh_POST) && count($page->mh_POST) > 0) {
					foreach ($page->mh_POST as $el) {
						if (empty($_POST[$el])) {
							redirect("index.php?p=99&do_missing__" . $el);
						}
					}
				}
				$page->D();
				return;
			}
		}

		// What shall we do?
		switch($action)
		{
			case "register": D::Register(); break;
			case "changePassword": D::ChangePassword(); break;
			case "logout": D::Logout(); redirect("index.php"); break;
			case "setOsuID": D::SetOsuID(); break;
			case "u": redirect("../ripple/index.php?u=".$_GET["data"]."&m=0"); break;
			case "recoverPassword": D::RecoverPassword(); break;
			case "saveUserSettings": D::saveUserSettings(); break;
			case "passwordFinishRecovery": D::PasswordFinishRecovery(); break;
			case "forgetEveryCookie": D::ForgetEveryCookie(); break;
			case "saveUserpage": D::SaveUserpage(); break;
			case "changeAvatar": D::ChangeAvatar(); break;
			case "sendReport": D::SendReport(); break;
			case "addRemoveFriend": D::AddRemoveFriend(); break;
			default: throw new Exception("Invalid action value"); break;

			// Admin functions, need sessionCheckAdmin() because can be performed only by admins
			case "generateBetaKeys": sessionCheckAdmin(); D::GenerateBetaKey(); break;
			case "allowDisallowBetaKey": sessionCheckAdmin(); D::AllowDisallowBetaKey(); break;
			case "publicPrivateBetaKey": sessionCheckAdmin(); D::PublicPrivateBetaKey(); break;
			case "removeBetaKey": sessionCheckAdmin(); D::RemoveBetaKey(); break;
			case "saveSystemSettings": sessionCheckAdmin(); D::SaveSystemSettings(); break;
			case "saveBanchoSettings": sessionCheckAdmin(); D::SaveBanchoSettings(); break;
			case "runCron": sessionCheckAdmin(); D::RunCron(); break;
			case "saveEditUser": sessionCheckAdmin(); D::SaveEditUser(); break;
			case "banUnbanUser": sessionCheckAdmin(); D::BanUnbanUser(); break;
			case "quickEditUser": sessionCheckAdmin(); D::QuickEditUser(); break;
			case "changeIdentity": sessionCheckAdmin(); D::ChangeIdentity(); break;
			case "saveDocFile": sessionCheckAdmin(); D::SaveDocFile(); break;
			case "removeDoc": sessionCheckAdmin(); D::RemoveDocFile(); break;
			case "removeBadge": sessionCheckAdmin(); D::RemoveBadge(); break;
			case "saveBadge": sessionCheckAdmin(); D::SaveBadge(); break;
			case "quickEditUserBadges": sessionCheckAdmin(); D::QuickEditUserBadges(); break;
			case "saveUserBadges": sessionCheckAdmin(); D::SaveUserBadges(); break;
			case "silenceUser": sessionCheckAdmin(); D::SilenceUser(); break;
			case "kickUser": sessionCheckAdmin(); D::KickUser(); break;
			case "resetAvatar": sessionCheckAdmin(); D::ResetAvatar(); break;
			case "openCloseReport": sessionCheckAdmin(); D::OpenCloseReport(); break;
			case "saveEditReport": sessionCheckAdmin(); D::SaveEditReport(); break;
		}
	}
	catch(Exception $e)
	{
		// Redirect to Exception page
		redirect("index.php?p=99&e=".$e->getMessage());
	}
?>
