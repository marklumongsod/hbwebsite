<?php


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="style.css">

    <style>
        @media only screen and (max-width: 760px),
        (min-device-width: 802px) and (max-device-width: 1020px){

            table, thead, tbody, th, td, tr {
                display: block;
            }

            .empty{
                display: none;
            }

            th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            td{
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
            }

            td:nth-of-type(1):before {
                content: "Sunday";
            }

            td:nth-of-type(2):before {
                content: "Monday";
            }

            td:nth-of-type(3):before {
                content: "Tuesday";
            }

            td:nth-of-type(4):before {
                content: "Wednesday";
            }

            td:nth-of-type(5):before {
                content: "Friday";
            }

            td:nth-of-type(6):before {
                content: "Saturday";
            }
        }

        @media only screen and (min-device-width: 320px) and (max-device-width: 480px){
            body {
                padding: 0;
                margin: 0;
            }
        }

        @media only screen and (min-device-width: 802ox)
    </style>
</head>
<body>
</body>
</html>