<?php
/*
script pour le transfert de contenus d'un langue à un autre 

pagination pour copy_translation.php

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

require_once('autoload.php');
include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

//Activates the circular reference collector if is not on
if(!gc_enabled())
    gc_enable();

function this_script_usage($cli, $script)
{
    $msg = "\nusage: php <path_to_script>/cptrans_part.php <to_lang#from_lang> <1:2:3:4:5:...>\n";
    $msg.= "\nwhere second parameters is a list of content_objects_IDs or 'all' for no pagination\n";
    $msg.= "\nNormally you don't have to run this script manually, it's called by copy_translation.php.";
    $msg.= "\n";
    $cli->error($msg);
    $script->shutdown();
    exit();
}


// init CLI 
$cli = SmartCLI::instance();

$cli->init( array(  'scriptname' => "cptrans_part.php",
                    'scriptpath' => "extension/translation-tools/bin/php/",
                    'logname'    => "cptrans",
                    'logmemory'     => true
                ) );

//init eZScript
$script = eZScript::instance( array( 'description' => ('Copy Action between two languages for a paginated N objects'),
                                     'use-session' => true,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );

$script->startup();

$options = $script->getOptions();

$script->initialize();

if ( count($options['arguments']) < 2 )
{
    this_script_usage($cli, $script);
}

$verbose = ( is_null($options['verbose']) ) ? false : $options['verbose'] ;
$quiet = ( is_null($options['quiet']) ) ? false : $options['quiet'] ;
$cli->setIsQuiet($quiet);
$cli->setVerbose($verbose, true);

$cli->showmem(); // <- memory usage check

// Login as admin user
$ini           = eZINI::instance();
$userCreatorID = $ini->variable( 'UserSettings', 'UserCreatorID' );
$user          = eZUser::fetch( $userCreatorID );
if( ( $user instanceof eZUser ) === false ) {
        $cli->error( 'Cannot get user object by userID = "' . $userCreatorID . '". ( See site.ini [UserSettings].UserCreatorID )' );
            $script->shutdown( 1 );
}
eZUser::setCurrentlyLoggedInUser( $user, $userCreatorID );


// script begin
$cli->beginout();
// parameters
$verbose = false;


$do_update_initial_language = owTranslationTools::checkDoUpdateInitialLanguage();

$langs = array_combine( array('tolang', 'fromlang'), explode("#", $options['arguments'][0]) );
$objects_id_list = ( $options['arguments'][1] == "all" )? "all" :  explode( ":", $options['arguments'][1] );
unset($options);

$cli->showmem(); // <- memory usage check

$toLangObj = eZContentLanguage::fetchByLocale( $langs['tolang'] );
$fromLangObj = eZContentLanguage::fetchByLocale( $langs['fromlang'] );

if( !$toLangObj || !$fromLangObj )
    exit_script( $cli, $script, $langs['tolang'] . " or " . $langs['fromlang'] . " are not in the list of the languages used on the site ", eZContentLanguage::fetchLocaleList() );


if( $objects_id_list == "all" )
{
    $fromlang_id = $fromLangObj->ID; 
    $objects_id_list = owTranslationTools::objectIDListByLangID($fromlang_id);
}

// list of objects that have traduction in $langs['tolang']
$toLang_objectID_list = owTranslationTools::objectIDListByLangID($toLangObj->ID);

// will contains objects that not have MainNode
$unassignedobjectsID = array();

$cli->showmem("before cptrans_part foreach"); // <- memory usage check

// update objects with $langs['tolang'], copy content from $langs['fromlang']
foreach( $objects_id_list as $object_id )
{
    $cli->showmem("begin cptrans_part foreach"); // <- memory usage check

    $current_object = eZContentObject::fetch( $object_id );
    if( !is_object($current_object) )
    {
        $errormsg = "eZContentObject::fetch return a non object : " . show($current_object);
        $errormsg.= "; object_id : " . $object_id;

        $cli->outputAndLog("error", $errormsg);
        break;
    }
    
    $cli->showmem("object fetched"); // <- memory usage check

    if($verbose)
        $cli->outputAndLog( "notice", "id : " . show( $current_object->ID ) );

    if($verbose)
    {
        $cli->gnotice( "can translate : " . show( $current_object->canTranslate() ) );
    } 

    owTranslationTools::updateAlwaysAvailable($object_id);

    $canPublishObject = (eZContentObjectTreeNode::fetch($current_object->mainParentNodeID()) instanceof eZContentObjectTreeNode) ? true : false;
    //$canPublishObject = true;

    if( !$canPublishObject )
    {
        $msg = "Parent of object with ID : $object_id need to be translated also !\n";
        $msg.= "mainParentNodeID : " . $current_object->mainParentNodeID();

        if( !is_numeric( $current_object->mainParentNodeID() ) )
        {
            $cli->outputAndLog("warning", $msg);
            $errormsg = "Object without Node !!! eZContentObject::mainPArentNodeID return : " . show($current_object->mainParentNodeID());
            $cli->outputAndLog("error", $errormsg);
            break;
        }

        $parent_object_id = owTranslationTools::getObjectIDFromNodeID($current_object->mainParentNodeID());
        $parent_fromlang = owTranslationTools::getLocaleByObjectID($parent_object_id, $langs['fromlang']);

        $msg.= "\nParent ObjectID : " . $parent_object_id;
        $msg.= "\nParent fromlang : " . $parent_fromlang;
        $cli->outputAndLog("warning", $msg);

        $tolang = $langs['tolang'];
        $commandtoexec = "php " . $cli->scriptpath . "cptrans_part.php $tolang#$parent_fromlang $parent_object_id";
        $commandtoexec.= ($verbose) ? " --verbose" : "" ;
        $commandtoexec.= ($quiet) ? " --quiet" : "" ;
        $cli->outputAndLog("notice", "execute command : " . $commandtoexec);
        exec($commandtoexec);

        $canPublishObject = (eZContentObjectTreeNode::fetch($current_object->mainParentNodeID()) instanceof eZContentObjectTreeNode) ? true : false;
    }

    if( !in_array($object_id,$toLang_objectID_list) && $canPublishObject )
    {
        // cleanup internal drafts for object
        $current_object->cleanupInternalDrafts();

        $cli->showmem("(before) : copyObjectAttributes"); // <- memory usage check

        $current_attributes = owTranslationTools::copyObjectAttributes($current_object);

        $cli->showmem("(after) : copyObjectAttributes"); // <- memory usage check

        $params = array( 'language' => $langs['tolang'],  'attributes' => $current_attributes);
        $update_success = owTranslationTools::publishNewTraductionFromLanguage( $current_object, $params, $langs['fromlang'] );
        //$update_success = eZContentFunctions::updateAndPublishObject($current_object, $params);


        $msg = "updateAndPublishObject ID=" . $current_object->ID . " : " . show($update_success);
        if( $update_success )
        {
            $cli->gnotice($msg);
        }
        else
        {
            $cli->outputAndLog("error", $msg );
        }

        $cli->showmem("(IF) : object updated"); // <- memory usage check
    }
    else
    {
        $cli->showmem("(ELSE) : object not updated"); // <- memory usage check

        if( in_array($object_id,$toLang_objectID_list) )
        {
            $errormsg = $langs['tolang'] . " already exists for object ";
            $errormsg.= "ID=" . $current_object->ID;
            $output_label = "notice";
        }
        else
        {
            $unassignedobjectsID[$current_object->ID] = $current_object->attribute("name");
            $errormsg = "cannot update object without MainParentNode ! ";
            $errormsg.= "node_id : " . $current_object->attribute('main_node_id');
            $errormsg.= "; object_id : " . $current_object->ID;
            $output_label = "error";
        }

        $cli->outputAndLog($output_label, $errormsg);
        $update_success = false;
    }

    if( !empty($update_success) )
    {
        // definie $tolang comme langue principale 
        // si $do_update_initial_language et $fromlang est l'actuelle langue principale
        if( $do_update_initial_language && $current_object->initialLanguageCode() == $langs['fromlang'] )
        {
            $updateInitialLanguage = owTranslationTools::updateObjectInitialLanguage($langs['tolang'], $current_object);
            if( !$updateInitialLanguage )
                $cli->outputAndLog("error", "Update Initial Language for Object " . $current_object->ID . " FAILED !");
            else
                $cli->gnotice("Initial Language is succesfully updated to $updateInitialLanguage" );
        }
    }

}

if( count($unassignedobjectsID) > 0 )
    $cli->outputAndLog("error", show($unassignedobjectsID));

//Forces collection of any existing garbage cycles
gc_collect_cycles();

$cli->colorout( "magenta-bg", "memory_get_peak_usage : \n" );
$cli->colorout( "magenta-bg", show(memory_get_peak_usage_hr()) );


// end of script
$cli->endout();
$script->shutdown();

?>
