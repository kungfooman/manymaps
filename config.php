<?php
	// filled by functions
	$GLOBALS["allMaps"] = array();
	$GLOBALS["ignoredMaps"] = array();

	// config
	$GLOBALS["config_project"] = "project_ns_maps_3";

	$GLOBALS["config_whitelist"] = array_flip(array(
		"mp_triway2",
		"mp_nemesis",
		"mp_deathrun",
		"mp_zombieladder_ns",
		"mp_maya",
		"mp_blocks",
		"mp_legacy",
		"mp_abyss",
		"ns_inviction",
		"mp_never",
		// ''these''  folder
		"mp_Hb",
		"mp_cod5_zomv3",
		"mp_vovel",
		"mp_ug_funbox",
		"mp_city",
		"mp_zom",
		"mp_28years",
		"mp_sewersV2",
		"mp_chamber",
		"mp_asdasdasdasdasdadasd",
	));
	
	function useProject($project) {
		return $project == $GLOBALS["config_project"];
	}
	
	function useMap($map) {
		return isset($GLOBALS["config_whitelist"][$map]);
	}
?>