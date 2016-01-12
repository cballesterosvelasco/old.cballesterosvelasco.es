php -r'$_SERVER["REQUEST_URI"] = "/es";require("./dispatcher.php");' > index.html
mkdir es; php -r'$_SERVER["REQUEST_URI"] = "/es";require("./dispatcher.php");' > es/index.html
mkdir en; php -r'$_SERVER["REQUEST_URI"] = "/en";require("./dispatcher.php");' > en/index.html
