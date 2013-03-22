<?php
/*
script pour nettoyer une langue et tous les contenus associés

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

require 'autoload.php';
include_once( 'extension/translation-tools/scripts/genericfunctions.php' );


// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

$cli->init( array(  'scriptname' => "cleanup_cptrans.php",
                    'scriptpath' => "extension/test/bin/php/",
                    'dictionary' => array(  "last-translations-version" => "<language_locale>",
                                            "version"                   => "<version_num>",
                                            "drafts"                    => "<version_status,...>"  )
                ) );

// init script
$script = eZScript::instance( array( 'description' => ('Delete content languages and related content'),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );

$script->startup();
$script->initialize();

// get options 
$options = $script->getOptions();
$arguments = $options['arguments'];
unset($options);

// verify arguments
if ( count($arguments) < 2 )
{
    script_usage($cli, $script, true);
}

// debut du script
$cli->beginout();

$fetch_limit = 5;

$action= $arguments[0];
$option = $arguments[1];
switch($action)
{
    case "last-translations-version" :
        $lang_locale = $option;
        $cli->warning("action '$action' : @TODO");
        break;

    case "version-if-last" :
    // @TODO if last
        $version_num = $option;
        $versions_count = owTranslationTools::countLastVersionsByVersion($version_num);
        $versions_list = owTranslationTools::fetchLastVersionListByVersion($version_num, $fetch_limit);
        foreach( $versions_list as $version )
        {
            $version->removeThis();
            $cli->gnotice("Version number $version_num is removed from contentobject with ID=" . $version->ContentObjectID );
        }
        if( $versions_count > count($versions_list) )
            $cli->warning("fetch limit is set to $fetch_limit. " . ($versions_count - count($versions_list))  . " left\nTo remove all versions run again this script until you'll get the success message");
        else
            $cli->gnotice("All objects'versions with number $version_num are succesfully removed from DB");
        break;

    case "drafts" :
        if( $option == "all" )
            $version_status_array = false;
        else
        {
            $version_status_array = explode(",",$option);
            $version_status_array = owTranslationTools::getVersionStatusFromString($version_status_array);
        }

        $processedCount = eZContentObjectVersion::removeVersions($version_status_array);
        $cli->gnotice("Cleaned up " . $processedCount . " drafts.");
        break;

    default :
        $cli->warning("action '$action' does not exist ! ");
        script_usage($cli, $script, true);
        break;
}



// fin du script
$cli->endout();
$script->shutdown();

?>
