<?php
/*
script pour le transfert de contenus d'un langue à un autre 
sur une sous-arborescence pour un node donné

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

require_once('autoload.php');
include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

//Activates the circular reference collector if is not on
if(!gc_enabled())
    gc_enable();

function this_script_usage($cli, $script)
{
    $msg = "\nusage: php <path_to_script>/copy_translation_subtree.php <to_language_locale> <from_language_locale> <paren_node_id>\n";
    $msg.= "\nfor a list of availables languages  : php <path_to_script>/help_cptrans.php list-locales\n";
    $msg.= "\nfor a list of help's actions run help_cptrans.php without parameters ;)\n";
    $cli->colorout("cyan", $msg);
    $script->shutdown();
    exit();
}


// init CLI 
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

$cli->init( array(  'scriptname' => "copy_translation_subtree.php",
                    'scriptpath' => "extension/translation-tools/bin/php/",
                    'logname'    => "cptrans"
                ) );

//init eZScript
$script = eZScript::instance( array( 'description' => ('Content Transfert between languages for a subtree'),
                                     'use-session' => true,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );

$script->startup();

$options = $script->getOptions();

$script->initialize();

$arguments = $options['arguments'];

if ( count($options['arguments']) < 3 )
{
    this_script_usage($cli, $script);
}

$verbose = ( is_null($options['verbose']) ) ? false : $options['verbose'] ;
$quiet = ( is_null($options['quiet']) ) ? false : $options['quiet'] ;
$cli->setIsQuiet($quiet);
$cli->setVerbose($verbose, true);

$cli->showmem(); // <- memory usage check

// script begin
$cli->beginout();

// parameters
$do_update_initial_language = owTranslationTools::checkDoUpdateInitialLanguage();
$pagination = owTranslationTools::getScriptPagination();

$siteaccess = $options['siteaccess'];
$tolang = $options['arguments'][0];
$fromlang = $options['arguments'][1];
$parent_node_id = $options['arguments'][2];

$cli->showmem(); // <- memory usage check

$toLangObj = eZContentLanguage::fetchByLocale( $tolang );
$fromLangObj = eZContentLanguage::fetchByLocale( $fromlang );

if( !$toLangObj || !$fromLangObj )
    exit_script( $cli, $script, "\"$tolang\" or \"$fromlang\" are not in the list of the languages used on the site ", eZContentLanguage::fetchLocaleList() );

$cli->showmem(); // <- memory usage check


$tolang_id = $toLangObj->ID; 
$fromlang_id = $fromLangObj->ID; 
if($verbose)
    $cli->gnotice( "$tolang id : $tolang_id; $fromlang id : $fromlang_id" );

$cli->showmem(); // <- memory usage check


// retrieve subtree of parent_node_id with translation of lang_ID
//$fromLang_objectID_list = owTranslationTools::objectIDListByLangID($fromlang_id);
$fromLang_objectID_list = owTranslationTools::subtreeObjectsIDListByLocale($fromlang, $parent_node_id);

$cli->showmem(); // <- memory usage check

//$tolang_objcount = owTranslationTools::langObjectsCountFromIdList($tolang_id, $fromLang_objectID_list);
//$fromlang_objcount = owTranslationTools::langObjectsCountFromIdList($fromlang_id, $fromLang_objectID_list);
$tolang_objcount = count( owTranslationTools::subtreeObjectsIDListByLocale($tolang, $parent_node_id) );
$fromlang_objcount = count( $fromLang_objectID_list );

$cli->gnotice( "$tolang objects count : $tolang_objcount; $fromlang objects count : $fromlang_objcount" );

$cli->showmem(); // <- memory usage check

//exit();
if( !is_array($fromLang_objectID_list) )
    exit_script($cli, $script, "owTranslationTools::subtreeObjectsIDListByLocale() return ", show($fromLang_objectID_list));

if( count($fromLang_objectID_list) <= 0 )
    exit_script($cli, $script, "there is no object with language $fromlang\nobjects list : ", $fromLang_objectID_list);
elseif($verbose)
    $cli->gnotice( show($fromLang_objectID_list) );

if( $pagination === 0 )
{
    $commandtoexec = "php " . $cli->scriptpath . "cptrans_part.php $tolang#$fromlang all";
    $commandtoexec.= ($quiet) ? " --quiet" : "" ;
    $commandtoexec.= ($verbose) ? " --verbose" : "" ;
    $cli->colorout("cyan", $commandtoexec);
    
    exec($commandtoexec);

    gc_collect_cycles();
}
else
{
    $paginated_id_list = array_chunk($fromLang_objectID_list, $pagination);
    
    $cli->showmem("before foreach"); // <- memory usage check
    
    // update objects with $tolang, copy content from $fromlang
    foreach( $paginated_id_list as $id_list_part )
    {
        $cli->showmem("begin foreach"); // <- memory usage check
    
        $string_id_list = implode(":", $id_list_part);
    
        $commandtoexec = "php " . $cli->scriptpath . "cptrans_part.php $tolang#$fromlang $string_id_list";
        $cli->colorout("cyan", $commandtoexec);
    
        exec($commandtoexec);
        gc_collect_cycles();
    
        $cli->showmem("end foreach"); // <- memory usage check
    }
}

// compte rendu du script
$cli->colorout("cyan", "Total objects processed : " . count($fromLang_objectID_list) );

// memory peak usage
$cli->colorout( "magenta-bg", "memory_get_peak_usage : \n" );
$cli->colorout( "magenta-bg", show(memory_get_peak_usage_hr()) );

// end of script
$cli->endout();
$script->shutdown();

?>
