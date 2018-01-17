<?php define("WOWPARSER", "TRUE"); ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="description" content="">
    <meta name="keywords" content="">
    <title>WoWhead SQL Parser</title>
    <link rel="stylesheet" type="text/css" href="style/main.css" title="Default Styles" media="screen">
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
</head>
<body>
    <div class="menu">
        <div class="logo">Wowhead item to sql parser</div>
    </div>
    <div class="content">
        <div class="form">
            <form method="post">
                <input type="text" name="entry" id="entry" placeholder="Insert item entry id" required>
            </form>
            <a class="generate" id="generate">Generate SQL</a>
            <a class="tos" href="tos">Usage & TOS</a>
        </div>
        <div class="result">
            <div class="item">
				
            </div>
            <textarea readonly></textarea>
        </div>
        <div class="footer">
            Copyright &copy; 2016 by Maxim Marco ( <a href="http://mmltools.com">MMLTools</a> ) All rights reserved.
            No part of this project may be reproduced, distributed, or transmitted in any form or by any means.
            All generated data comes from <a href="http://wowhead.com">WoWHead</a>, this website is no way associated with WoWHead or any other afiliates
        </div>
    </div>
</body>
<script>
    $( "#generate" ).click(function() {
        var item = $('#entry').val();
        $('.result').css("display", "inline-block");
        $('.result').html('<div class="loader"></div>');
        $.ajax({
            type: 'POST',
            url: 'engine/xml.php',
            data: ({'item' : item}),
            success: function(data) {
                if (data)
                    $('.result').html(data);
                else
                    $('.result').html("<div class='error'>An error occured while processing your request</div>");
            }
        });});
</script>
</html>