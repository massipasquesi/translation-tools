<?php

// fonction d'aide sur le mode d'usage du script
function script_usage($cli, $script, $options = false)
{
    $options = ($options)? "<options>" : "";
    $msg = "usage : php " . $cli->scriptpath . $cli->scriptname . " <action> $options";
    $msg.= "\nlist of availables actions : " . show($cli->dictionary);
    $cli->emptyline();
    $cli->warning($msg);
    $script->shutdown();
    exit();
}

// fonction d'arret de script générique
function exit_script($cli, $script, $errormsg ="", $debug_var = "no_thanks")
{
    $cli->outputAndLog("error", $errormsg);

    if( $debug_var != "no_thanks" )
        $cli->outputAndLog("error", show($debug_var) );

    $cli->endout(false, "red-bg");
    $script->shutdown();
    exit();
}

// fonction pour afficher les variable en console
function show($var)
{
	if( is_bool($var) )
	{
		$bool = ( $var )? "true" : "false";
		$show = gettype($var) . " : " . $bool;
	}
    elseif( is_null($var) )
        $show = gettype($var);
	else
		$show = print_r($var,true);
	
	return $show; 
}

/*
 * fonctions relative à l'utilisation de la memoire
 */

function convert($size)
{
	$unit=array('b','kb','mb','gb','tb','pb');
	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function memory_get_usage_hr()
{
	return convert(memory_get_usage(true));
}

function memory_get_peak_usage_hr()
{
	return convert(memory_get_peak_usage(true));
}

/*
 * fonctions pour des exit et des var_dump
 */
function vd($var)
{
	return var_dump($var);
}

function evd($var)
{
	exit(vd($var));
}

function mvd($array)
{
	if(!is_array($array))
		return vd($array);
		
	foreach($array as $var)
	{
		vd($var);
	}
}

function emvd($array)
{
	if(!is_array($array))
		exit(vd($array));
		
	exit(mvd($array));
}

function ivd($name, $var)
{
	echo($name . " : \n");
	vd($var);
}

function eivd($name, $var)
{
	echo($name . " : \n");
	exit(vd($var));
}

function bp($msg = false)
{
	if(!$msg)
		exit("break-point. ");
	else
		exit("break-point : " . $msg . ". ");
}

?>
