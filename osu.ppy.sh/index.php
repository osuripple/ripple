<?php
	// Get functions
	require_once("./inc/functions.php");
	
	// Bancho/frontend loader
	if ($_SERVER["HTTP_HOST"] == "c.ppy.sh" || $_SERVER["HTTP_HOST"] == "c1.ppy.sh")
	{
		// Do bancho stuff
		require_once("./inc/bancho.php");
		
		// Run server stuff or output webpage
		if ($_SERVER["HTTP_USER_AGENT"] == "osu!")
			banchoServer();
		else
			banchoWeb();
		
		// Don't process anything from the web frontend
		die();
	}
	
	// Do frontend stuff	
?>

<?php
    // Frontend stuff
    // We're using ob_start to safely send headers while we're processing the script initially.
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Dynamic title -->
    <?php if (isset($_GET["u"]) && !empty($_GET["u"])) setTitle("u"); ?>
    <?php if (isset($_GET["p"]) && !empty($_GET["p"])) $p = $_GET["p"]; else $p = 1; setTitle($p); ?>

    <!-- Bootstrap Core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap select CSS -->
    <link href="./css/bootstrap-select.min.css" rel="stylesheet">

    <!-- Bootstrap Font Awesome Picker CSS -->
    <link href="./css/fontawesome-iconpicker.min.css" rel="stylesheet">

    <!-- Bootstrap Color Picker CSS -->
    <link href="./css/bootstrap-colorpicker.min.css" rel="stylesheet">

    <!-- SCEditor CSS -->
    <link rel="stylesheet" href="./css/themes/default.css" type="text/css" media="all" />

    <!-- Animate CSS -->
    <link rel="stylesheet" href="./css/animate.css">

    <!-- Custom CSS -->
    <link href="./css/style-desktop.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="favicon.png"/>
</head>

<body>
    <?php
    // Start session with user if we got a valid cookie.
    startSessionIfNotStarted();
    $c = new RememberCookieHandler();
    if ($c->Check())
      $c->Validate();
    ?>
    <!-- Navbar -->
    <?php printNavbar(); ?>

    <!-- Page content (< 100: Normal pages, >= 100: Admin CP pages) -->
    <?php
    if ($p < 100)
    {
        // Normal page, print normal layout (will fix this in next commit, dw)
        echo('
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div id="content">');
                        printPage($p);
                        echo('
                    </div>
                </div>
            </div>
        </div>');
    }
    else
    {
        // Admin cp page, print admin cp layout
        printPage($p);
    }
    ?>

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Bootstrap Select JavaScript -->
    <script src="js/bootstrap-select.min.js"></script>

    <!-- Bootstrap Font Awesome Picker JavaScript -->
    <script src="js/fontawesome-iconpicker.min.js"></script>

    <!-- Bootstrap Color Picker JavaScript -->
    <script src="js/bootstrap-colorpicker.min.js"></script>

    <!-- SCEditor JavaScript -->
    <script type="text/javascript" src="js/jquery.sceditor.bbcode.js"></script>

    <!-- Custom JavaScript for every page -->
    <script type="text/javascript">
        // Initialize stuff
        $('.icp-auto').iconpicker();
        $('.colorpicker').colorpicker({format:"hex"});
        $('.sceditor').sceditor({plugins: "bbcode", resizeEnabled: false, toolbarExclude: "font,table,code,quote,ltr,rtl" , style: "css/jquery.sceditor.default.css"});
        $(".spoiler-trigger").click(function() {$(this).parent().next().collapse('toggle');});

        // Are you sure window
        function sure($redirect)
        {
            var r = confirm("Are you sure?");
            if (r == true) window.location.replace($redirect);
        }
    </script>


    <!-- Custom JavaScript for this page here -->
    <?php
        switch($p)
        {
            // Admin cp - beta keys
            case 105: echo('
            <script type="text/javascript">
            var text = "Digital Insanity";

            for(var i in text) {
              if(text[i] === " ") {
                $(".wavetext").append( $("<span>").html("&nbsp;") );
              } else {
                $(".wavetext").append( $("<span>").text(text[i]) );
              }
            }
            </script>

            <script type="text/javascript">
                // Ripple insanity
                $("#addBetaKeyModal").on("shown.bs.modal", function () {
                    audio = new Audio("./audio/keygen.mp3");
                    audio.play();
                });

                $("#addBetaKeyModal").on("hidden.bs.modal", function () {
                    audio.pause();
                });
            </script>'); break;

            // Admin cp - edit user
            case 103: echo('
                <script type="text/javascript">
                    function censorUserpage()
                    {
                        document.getElementsByName("up")[0].value = "[i]:peppy:Userpage resetted by an admin.:peppy:[/i]";
                    }
                </script>
                '); break;
        }
    ?>
</body>

</html>
<?php
ob_end_flush();
