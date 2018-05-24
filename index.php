<?php
//  by onn@onndo.com 10.12.2015
@ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(60);
$tcounter = microtime(true);

$required = array(
  "func" => array(
    "file_get_contents",
    "curl_init"
  ),
  "class" => array(
    "DOMXPath",
    "DOMDocument"
  )
);

foreach($required["func"] as $func) {
  if(!function_exists($func)) {
    print "<span style='color:red'>Function <strong>$func</strong> is missed, script may work improperly.</span><br>";
  }
}
foreach($required["class"] as $c) {
  if(!class_exists($c)) {
    print "<span style='color:red'>Class <strong>$c</strong> is missed, script may work improperly.</span><br>";
  }
}

include('./parser.php');
$parser = new parser();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title> small parser </title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<style>
		.header-text {
		 margin-top: 20px;
		}
		.column-cust {
		margin-bottom: .25rem!important;
	        margin-top: .25rem!important;
		}
		.select-cust {
		margin-right: .5rem!important;
		}
		.row-cust {
		margin-top: 6rem!important;
		}
		.input-cust {
		margin-right: .5rem!important;
		}

	</style>
</head>

<body>
  <div class="container">
    <!-- <center> -->
    <h4 class="text-center header-text"> Micro parser </h4>
    <form method="post" name="parser" action="?" accept-charset="ISO-8859-1">
    <div class="form-row">
          <div class="col-md-2 column-cust">
            <select class="form-control select-cust" name="site">
              <option selected>Choose...</option>
              <option value="cdw.com">cdw.com</option>
              <option value="newegg.com">newegg.com</option>
              <option value="bhphotovideo.com">bhphotovideo.com</option>
              <option value="tigerdirect.com">tigerdirect.com</option>
              <option value="biz.tigerdirect.com">biz.tigerdirect.com</option>
            </select>
          </div>
          <div class="col-md-9 column-cust">
            <input class="form-control input-cust" type="text" name="url" required="required" placeholder="Input product url" value="<?=isset($_POST['url'])?$_POST['url']:''?>"
            />
          </div>
          <div class="col-md-1 column-cust">
            <button class="btn btn-primary" type="submit"> Go </button>
          </div>
    </div>
    </form>
    <div class="row row-cust">
      <h5 class="font-italic">Result: <?=isset($_POST['site'])?$_POST['site']:''?></h5>
    </div>
    <div class="row">
      <textarea class="form-control" rows="15" cols="80">
    <?=htmlentities($parser->get_result())?>
</textarea>
    </div>
    <div class="row">
      <h5 class="font-italic">Time: <?=number_format((microtime(true) - $tcounter),4, '.', '')?> sec</h5>
    </div>
    <div class="row row-cust">
      <?=$parser->get_result()?>
    </div>

    <!-- </center> -->
  </div>
</body>

</html>
