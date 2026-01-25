<?php
require_once 'Models/ExampleModel.php';
$model = new ExampleModel();
?><!DOCTYPE html>
<html>
<head>
    <title>FnCashBack</title>
</head>
<body>
    <h1><?php echo $model->getMessage(); ?></h1>
</body>
</html>
