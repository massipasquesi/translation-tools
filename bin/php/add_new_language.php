<?php
/*
script pour ajouter une nouvelle langue pour les contenus 

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

require 'autoload.php';
include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

function this_script_usage($cli, $script)
{
    $msg = "\nusage: php <path_to_script>/add_new_language.php <new_language_locale>";
    $msg.= "\nfor a list of availables languages  : php <path_to_script>/help_cptrans.php list-locales";
    $cli->warning($msg);
    $script->shutdown();
    exit();
}

// init CLI & eZScript
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->setScriptName("add_new_language.php");

$script = eZScript::instance( array( 'description' => ('Add new content languages'),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );

$script->startup();
$script->initialize();
$options = $script->getOptions();
$arguments = $options['arguments'];

if ( count($options['arguments']) < 1 )
{
    this_script_usage($cli, $script);
}

// debut du script
$cli->beginout();

$siteaccess = $options['siteaccess'];
$newlang = $options['arguments'][0];
unset($options);

if( !in_array( $newlang, eZLocale::localeList() ) )
    exit_script($cli, $script, "\"$newlang\" is not in the list of availables languages ");

if ( !eZContentLanguage::fetchByLocale( $newlang ) )
{
    $locale = eZLocale::instance( $newlang );
    if ( $locale->isValid() )
    {
        $newLangObj = eZContentLanguage::addLanguage( $locale->localeCode() );
    }
    else
    {
        // The locale cannot be used so exit with error.
        exit_script($cli, $script, "eZLocale::instance($newlang) return is not valid ");
    }
}

$cli->gnotice( show($newLangObj) );

if( empty($newLangObj->Locale) )
    $newLangObj->removeThis();



// fin du script
$cli->endout();
$script->shutdown();

?>
