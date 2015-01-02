<style>
	body {
		color: white;
		background-color: #333;
	}
	.iwd {
		color: lightblue;
		background-color: black;
		font-size: 2em;
		border-bottom: 1px solid lime;
	}
	.warning {
		color: orange;
	}
	.map {
		color: lime;
	}
	.link {
		color: yellow;
	}
	.success {
		color: green;
	}
	.fail {
		color: red;
	}
	.intend {
		margin-left: 20px;
	}
	
	form > textarea {
		height: 90px;
		width: 600px;
	}
</style>

<!--
chmod 777 /home/kung/public_html/ -R
chown www-data:www-data /home/kung/public_html/ -R
chown kung:kung /home/kung/public_html/manymaps/manymaps.php
-->

<pre>
say hello you!
zip mp_cod5_zombie_v4
</pre>
<form action="#" method="post">
	<textarea name="minishell"></textarea>
	<input type="submit" value="Execute!">
</form>

<?php
	function checkRequirements() {
		if (exec("echo TEST") != "TEST") {
			echo "<div class=fail>EXEC DOES NOT WORK!</div>";
		} else {
			echo "EXEC DOES WORK";
		}
	}
	checkRequirements();
	
	function minishell($exec) {
		echo "<pre>";
		$cmds = explode("\n", $_POST["minishell"]);
		foreach ($cmds as $cmd) {
			$all = explode(" ", $cmd);
			switch ($all[0]) {
				case "say":
					for ($i=1; $i<count($all); $i++)
						echo $all[$i] . " ";
					break;
				case "zip":
					$basename = $all[1];
					echo "Zip map \"$basename\"<br>";
					if (file_exists("Library/$basename"))
						system("cd Library/$basename; zip -r ../$basename.iwd .");
					else
						echo "Library/$basename does not exist!\n";
					break;
			}
		}
		echo "</pre>";
	}

	function initStockfiles($filename) {
		// C:\Users\Admin\Desktop\cod2_1_2>dir /S /B > C:\Users\Admin\Desktop\filelist.txt
		// then replace the absolute path with "", replace \ with /
		$stockfiles = array();
		$tmps = explode("\n", file_get_contents($filename));
		foreach ($tmps as $tmp) {
			$val = trim($tmp);
			if (strlen($val) == 0)
				continue;
			$stockfiles[] = $val;
		}
		// convert values to keys for fast hash search
		$GLOBALS["stockfiles"] = array_flip($stockfiles);
		
		echo "<div>Stockfiles $filename: " . count($GLOBALS["stockfiles"]) . "</div>";
		//var_dump($stockfiles);
		
		return true;
	}
	function isStockfile($filename)
	{
		return isset($GLOBALS["stockfiles"][$filename]);
	}
	
	
	function getModelsOfBsp($filename) {
		$models = array();
		$lines = explode("\n", file_get_contents($filename));
		foreach ($lines as $line) {
			if ( ! strstr($line, "\"model\""))
				continue; // "model" "xmodel/weapon_panzerschreck"
			$tmp = explode(" ", $line);
			$name = trim($tmp[1], "\"");
			if ($name[0] == "*")
				continue; // "model" "*7"
			//echo $line . "<br>";
			$models[$name] = $name; // key=value, prevent doubles
		}
		return $models;
	}

	function getMaterialsOfBsp($filename) {
		$cmd = "./luajit -l cod2info -e 'bsp(\"$filename\");listMaterials()'";
		echo "$cmd<br>";
		$materials = array(); // clean array, its a reference
		exec($cmd, $materials);
		return $materials;
	}

	function getImagesForMaterial($filename) {
		$cmd = "./luajit -l cod2info -e 'getImagesOfMaterial(\"$filename\");'";
		echo "$cmd";
		//system($cmd);
		$images = array(); // clean array, its a reference
		exec($cmd, $images);
		return $images;
	}
	
	// mylink() also creates the needed subdirs
	// returns true when the link was already in place or got created
	function mylink($from, $to, $info="no info :(") {
		if ($GLOBALS["debugLink"])
			echo "<div class=link>LINK FROM=$from TO=$to</div>";

		// create empty files automatically for each linked file.
		// this also prevents that other impure .iwd's become loaded (e.g. they have a loadscreen and you dont)
		// remember to use this also for file_put_contents() e.g. for mp/$basename.csv (for loadscreen)
		
		$splitPath = explode("/", $from);
		$project = $splitPath[0];
		$mapname = $splitPath[1];
		$fromWithoutMapname = substr($from, strlen($project)+1+strlen($mapname)+1); // +1 for each slash
		$emptyFile = "$project/Library/empty/$fromWithoutMapname";
		@mkdir(dirname($emptyFile), 0777, true);
		file_put_contents($emptyFile, "");

		echo "FROM:$from<br>";
		echo "project:$project<br>";
		echo "mapname:$mapname<br>";
		echo "fromWithoutMapname:$fromWithoutMapname<br>";
		// easy switcher: bool * preferSome
		$prefer = "missing";
		
		if ($prefer == "missing") {
			$newFrom = "$project/missing/$fromWithoutMapname";
			if (file_exists($newFrom)) {
				//echo $fromWithoutMapname;
				$extra = "<span class=success>file exists in missing-folder though (newFrom=$newFrom)!</span>";
				//echo "<div class=warning>WARNING LINK($info): File $from does not exist! $extra</div>";
				
				$from = $newFrom;
				echo "<div class=link>LINK FROM=$from TO=$to</div>";
				$GLOBALS["newFrom"] = $newFrom;
			} elseif (file_exists($from)) {
				// great, file exists, do nothing here
			} else {
				$extra = "<span class=fail>Not in missing-dir also (newFrom=$newFrom).</span>";
				echo "<div class=warning>WARNING LINK($info): File $from does not exist! $extra</div>";
				return false;
			}
		} else { // prefer original files
			if ( ! file_exists($from)) {
				$newFrom = "$project/missing/$fromWithoutMapname";
				if (file_exists($newFrom))
				{
					//echo $fromWithoutMapname;
					$extra = "<span class=success>file exists in missing-folder though (newFrom=$newFrom)!</span>";
					//echo "<div class=warning>WARNING LINK($info): File $from does not exist! $extra</div>";
					
					$from = $newFrom;
					echo "<div class=link>LINK FROM=$from TO=$to</div>";
					$GLOBALS["newFrom"] = $newFrom;
				} else {
					$extra = "<span class=fail>Not in missing-dir also (newFrom=$newFrom).</span>";
					echo "<div class=warning>WARNING LINK($info): File $from does not exist! $extra</div>";
					return false;
				}
			}
		}
		
		if ( ! file_exists($to)) {
			@mkdir(dirname($to), 0777, true);
			link($from, $to);
		}
		return true;
	}
	
	function linkMaterial($from, $to, $dir, $library, $basename) {
		$GLOBALS["newFrom"] = null;
		$fileExists = mylink($from, $to, "Missing Material!");
		
		
/*
WARNING: Could not find material 'filter_symmetric_7'
ERROR: Couldn't open techniqueSet 'materials_dx7/techniquesets/filter_symmetric_8.techset'
WARNING: Could not find material 'filter_symmetric_8'
ERROR: material 'loadscreen_mp_noko' has zero length
WARNING: Could not find material 'loadscreen_mp_noko'
ERROR: image 'images/loadscreen_mp_loopbruggenland.iwi' has 0 length
Error during initialization
*/
		// create empty .iwi
		//$emptyFile = "Library/empty/images/loadscreen_$basename.iwi";
		//@mkdir(dirname($emptyFile), 0777, true);
		//file_put_contents($emptyFile, "");
		
		
		if ( ! $fileExists)
			return false;
		
		// this happens when the file got found in missing/-dir
		if ( ! is_null($GLOBALS["newFrom"]))
			$from = $GLOBALS["newFrom"];
		
		$images = getImagesForMaterial($from);
		//echo "Images in $from: " . (count($images)-1) . "<br>"; // -1 for identitynormalmap
		foreach ($images as $image) {
			if ($image == "\$identitynormalmap.iwi")
				continue;
			//echo "[[$image]]<br>";
			mylink("$dir/images/$image", "$library/$basename/images/$image", "Missing Image in Material=$from");
		}
		return true;
	}
	
	function handleArchive($project, $dir, $doZip) {
		$maps = glob("$project/$dir/maps/mp/*.d3dbsp");
		foreach ($maps as $map)
		{
			$basename = basename($map, ".d3dbsp");
			echo "<div class=map>Map: <b>$map</b></div>";
			echo "<div class=intend>";

			$models = getModelsOfBsp("$project/$dir/maps/mp/$basename.d3dbsp");
			foreach ($models as $model) {
				if ( ! isStockfile($model))
					echo "<div class=warning>Missing $model</div>";
			}

			@unlink("$project/$dir/maps/mp/$basename.csv"); // we generate them all
			@unlink("$project/$dir/maps/mp/$basename.d3dprt"); // not needed

			if ($basename == "mp_cod5_zombie_v4" || $basename == "mp_yoshi_kong_v3") {
				echo "use extra fx!";
				mylink("$project/$dir/fx/yoko/zapper.efx", "$project/Library/$basename/fx/yoko/zapper.efx");
			}
			
			mylink("$project/$dir/maps/mp/$basename.d3dbsp", "$project/Library/$basename/maps/mp/$basename.d3dbsp");
			$hasLoadscreen = linkMaterial("$project/$dir/materials/loadscreen_".strtolower($basename), "$project/Library/$basename/materials/loadscreen_".strtolower($basename), "$project/$dir", "$project/Library", $basename); // would like a class for this kinda..
			if ($hasLoadscreen) {
				@file_put_contents("$project/Library/$basename/maps/mp/$basename.csv", "levelBriefing,loadscreen_".strtolower($basename));
				@file_put_contents("$project/Library/empty/maps/mp/$basename.csv", ""); // just for empty.iwd
			} else {
				// use some standard shader, otherwise it sucks in impure old .iwd's...
				@file_put_contents("$project/Library/$basename/maps/mp/$basename.csv", "levelBriefing,black");
				@file_put_contents("$project/Library/empty/maps/mp/$basename.csv", ""); // just for empty.iwd
			}
			mylink("$project/$dir/mp/$basename.arena", "$project/Library/$basename/mp/$basename.arena"); // TODO feature: write "gametype" by available spawns etc. and check general syntax

			
			$materials = getMaterialsOfBsp("$project/$dir/maps/mp/$basename.d3dbsp");
			echo "Materials (" . count($materials) . "): ";
			foreach ($materials as $material) {
				// check if mapper overwrote stock material?
				if (isStockfile("materials/$material")) {
					echo "<span style=color:lightblue>$material, </span>";
					continue;
				} else {
					echo "<span style=color:lime>$material, </span>";
				}
				linkMaterial("$project/$dir/materials/$material", "$project/Library/$basename/materials/$material", "$project/$dir", "$project/Library", $basename);
			}

			if ($doZip)
			{
				echo "<pre>";
				system("cd $project/Library/$basename; zip -r ../$basename.iwd .");
				echo "</pre>";
			}

			echo "</div>"; // div class=intend
		}
	}
	
	function getMaps($project) {
		$maps = glob("$project/Library/*", GLOB_ONLYDIR);
		$maps = array_map("basename", $maps);
		// TODO: remove empty-folder out ouf Library?
		$maps = array_filter($maps, function($name){return !($name=="empty");});		
		return $maps;
	}

	function parseArena($filename) {
		$content = file_get_contents($filename);
		//$parts = explode(" ", $content);
		//$parts = array_filter($parts, "strlen");
		
		// \s* is any number of whitespace
		// ".*" is greedy till last ", use .*? to make it stop ASAP (lazy)
		preg_match_all('/(map|longname|gametype)\s*(".*?")/', $content, $all);
		
		$arena = array();
		$arena[$all[1][0]] = trim($all[2][0], '"');
		$arena[$all[1][1]] = trim($all[2][1], '"');
		$arena[$all[1][2]] = trim($all[2][2], '"');
		
		//echo $content;
		//var_dump($arena);
		//echo "<hr>";
		return $arena;
	}
	
	
	function zipEmptyIWD($project) {
		$cmd = "cd $project/Library/empty; zip -r ../empty.iwd .";
		echo "CMD: $cmd<pre>";
		system($cmd);
		echo "</pre>";		
		echo "<hr>";
	}
	
	function printDownloadLinksOfEachMap($project) {
		$maps = getMaps($project);
		foreach ($maps as $map) {
			// -N is overwriting
			echo "wget -N {$GLOBALS["wwwBaseURL"]}/$project/Library/$map.iwd<br>";
		}
		echo "echo 'Have fun testing!'";
	}
	
	function main() {
		$GLOBALS["debugLink"] = 1;
	
		$GLOBALS["doZipArchives"] = 1;
		$GLOBALS["doZipEmpty"] = 1;
		$GLOBALS["doCleanLibrary"] = 1;
		

		$library = "Library";

		$GLOBALS["wwwBaseURL"] = "http://killtube.org/~manymaps/manymaps";
		
		
		initStockfiles("cod2_1_2.txt");
		$projects = glob("project_*");
		foreach ($projects as $project) {
			echo "Project: $project <br>";
			
			if ($project != "project_ns_maps_3")
				continue;
				
			if ($GLOBALS["doCleanLibrary"])
				system("rm $project/Library -r");
				
			$iwds = glob("$project/*.iwd");
			foreach ($iwds as $iwd) {
				$dir = basename($iwd, ".iwd");
				if ( ! file_exists("$project/$dir")) {
					$cmd = "cd $project; unzip $dir.iwd -d $dir";
					echo "<div class=none>$cmd</div>";
					echo "<pre>";
					system($cmd);
					echo "</pre>";
				}
				echo "<div class=iwd>Project: <b>$project</b> Dir: <b>$dir</b></div>";
				echo "<div class=intend>";
				handleArchive($project, $dir, $GLOBALS["doZipArchives"]);
				echo "</div>";
			}
			if ($GLOBALS["doZipEmpty"])
				zipEmptyIWD($project);
			printDownloadLinksOfEachMap($project);
		}
		

		
		

		
		function createMapvoteThumbnails($project) {
			//system("rm thumbnails -r");
			//system("rm thumbnails.iwd");
			$maps = getMaps($project);
			foreach ($maps as $map) {
				$lowermap = strtolower($map); // material names arent allowed to be upper case
				$cmd = "python2.6 -c 'from create_thumbnails import *; createThumbnailOfMaterial(\"$lowermap\", \"$project/Library/$map\")'";
				echo "$cmd<br>";
				//system($cmd);
			}
			echo "cd thumbnails; zip -r ../thumbnails.iwd .";
		}
		
		function createMapvoteScript($project) {
			$maps = getMaps($project);
			echo "<pre>";
			function qmark($str) {
				return "\"$str\"";
			}

			
			foreach ($maps as $map) {
				$lowermap = strtolower($map); // material names arent allowed to be upper case
				//$thumbnail = "thumbnail_" . $lowermap;
				$thumbnail = "t_" . substr($lowermap, 3); // replace all the "thumbnail_mp" with "t" to reduce gamestate (precache)
				
				$arena = parseArena("$project/Library/$map/mp/$map.arena");
				
				$longname = $arena["longname"];
				
				// reduces also gamestate
				for ($i=0; $i<=9; $i++)
					$longname = trim(str_replace("^$i", "", $longname));
				
				$a = qmark($map) . ",";
				$b = qmark($thumbnail) . ",";
				$c = "&".qmark($longname) . ",";
				$d = qmark($longname);
				$output = sprintf("maps\mp\gametypes\_mapvote_kung::addMap(%-25s %-25s %-40s %s);\n", $a, $b, $c, $d);
				echo htmlspecialchars($output);
			}
			echo "</pre>";
		}
		
		function createMapvoteMenu($project) {
			$maps = getMaps($project);
			echo "<pre>";
			
			$tabs = "\t\t";
			$n = count($maps);
			echo "$tabs// number of maps: $n\n";
			
			/*
			$i = 1; // why the fuck it starts at 1
			$max = ceil(sqrt($n));
			for ($a=0; $a<$max; $a++)
				for ($b=0; $b<5; $b++) {
					if ( ! ($i-1 < $n) )
						break;
						

					$map = $maps[$i];
					
					$width = 64 + 10;
					$height= 48 + 10;
					
					$left = $b * $width + 50;
					$top = $a * $height + 50;
					
					$shader = "t_" . substr(strtolower($map), 3);
					$shortname = substr($map, 3);
					printf("{$tabs}MAPVOTE(%4d %4d 64 48, \"$shortname\", \"$shader\") // $i a=$a b=$b\n", $left, $top);
					$i++;
				}
			*/
			
			$page = 1; // 0 = hidden
			$i = 0;
			foreach ($maps as $map) {
				if ($map == "mp_cod5_zombie_v2" || $map == "mp_cod5_zombie_v3")
					continue; // i only want v4
			
				if ($i == 35) {
					$i = 0;
					$page++;
				}
				$row = floor($i / 5);
				$col = $i % 5;
				
				//printf("$i: $row $col\n");
				
				
				$width = 64 + 10;
				$height= 48 + 10;
				
				$left = $col * $width + 50;
				$top = $row * $height + 50;
				
				$shader = "t_" . substr(strtolower($map), 3);
				$shortname = substr($map, 3);
				printf("{$tabs}MAPVOTE(%4d %4d 64 48, \"$page\", %-20s, %-20s) // %2s $row:$col\n", $left, $top, "\"$shortname\"", "\"$shader\"", $i);
				
				$i++;
			}
			echo "</pre>";
		}
		
		
		createMapvoteThumbnails($project);
		createMapvoteScript($project);
		createMapvoteMenu($project);
		
		function createCaseSensitiveLinks($project) {
			$maps = getMaps($project);
			echo "<pre>";
			foreach ($maps as $map) {
				$low = strtolower($map);
				if ($map != $low)
					echo "ln -s $map.gsc $low.gsc\n";
			}
			echo "</pre>";
		}
		createCaseSensitiveLinks($project);
		
		if (isset($_POST["minishell"]))
			minishell($_POST["minishell"]);
	}
	main();