<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
$keyword = $_GET['keyword'];
$amount = $_GET['amount'];

#echo "PATH : ". get_include_path();
#set_include_path(get_include_path() . get_include_path().'/phpseclib');
include('Net/SSH2.php');

$ssh = new Net_SSH2('taito.csc.fi');
if (!$ssh->login('babst', 'Jsts8472')) {
    exit('Login Failed');
}

echo $ssh->exec('pwd');
echo $ssh->exec('ls -la');



/*
#$output = shell_exec('cd .. ; echo $USER ; ssh -i /home/ec2-user/.ssh/taitoApache.rsa babst@taito.csc.fi  \'bash -s\' < getDataForWord.sh ' . $keyword . " " . $amount . " 2>&1");
$output = shell_exec('cd .. ; echo $USER ; ssh -i /var/www/.ssh/taitoApache.rsa babst@taito.csc.fi echo bouyeah ls 2>&1');

#$output = shell_exec("cd .. ; pwd;  bash getDataForWord.sh kamusi 5 2>&1");# . $keyword . " " . $amount);
#$output = shell_exec('echo blabla; groups $USER; echo $USER');
*/
echo($output);

?>