<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
$timestart=microtime(true);
require_once("../../www/config.php");
binnewz_rss(200, 660);
//binnewz_rss($argv[1], 5);


function binnewz_rss ($days, $page) {
	disable_group_all();
	//$days = floor((time() - mktime(0,0,0, date("m")-$month, date("d"), date('Y'))) / 3600 / 24)+1;
        //exec('rm ./films-hd_all');
	while($page != -1){
		//exec('phantomjs ./binnewz.js '.$page.' >> ./films-hd_all');
		$page--;
	}
	$post_get = file_get_contents('./films-hd_all');
	$post_get = str_replace('<img src="img/password.png">  ', '', $post_get);
        $post_get = str_replace('<span id="green">', '', $post_get);
        $post_get = str_replace('<span id="red">', '', $post_get);
        $post_get = str_replace('  ', '', $post_get);
	//echo $post_get;
	$post_regex = '/b>((?=<).*>  |)(.*)((?=<)<.*>| )\((....)\).*bleu"><a href="" title="">(.{5,20})<\/a.*>(.*)<.*q=(.*)" tar.*<\/a>-->\n.*\n.*\n.*\n.*\n.*\n.*\n.*nfo\.php\?link=(......)/';
	preg_match_all( $post_regex , $post_get , $post_matches , PREG_SET_ORDER );
	add_group_post( $post_matches , $days);
        add_regex_post( $post_matches);
        echo "[INFO] **** STEP 1 **** Launch update_binaries.php... PLEASE WAIT !\n";
	exec('php update_binaries.php > update_binaries.log');
        echo "[INFO] **** STEP 2 **** Launch backfill_threaded.php... PLEASE WAIT ! (MORE 48H)\n";
        exec('php backfill_threaded.php > backfill_threaded.log');
        echo "[INFO] **** STEP 3 **** Prepare binaries who dont have a realease yet, for apply new incoming regex on them when update_releases will be launch... PLEASE WAIT !\n";
        $my_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
        $sql_select = 'UPDATE `binaries` SET procstat = 1 WHERE procstat = 3;';
        if ( $my_db->query($sql_select) === FALSE ) { echo "[ERROR]--------->$sql_select<---------\n***".$my_db->error."***\n"; }
        $my_db->close();
        echo "[INFO] **** STEP 4 **** Launch update_releases.php... PLEASE WAIT !\n";
        exec('php update_releases.php > update_releases.log');
        echo "[INFO] **** STEP 5 **** Launch update_release_post... PLEASE WAIT !\n";
        update_release_post( $post_matches);
        echo "[INFO] **** ALL GOOD ****\n";
}

function add_group_post( $post_matches , $days ) {
	$my_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	$post_group_conc = "";
	foreach ( $post_matches as $post_values ) {
		echo $post_values[1];
                echo "\t" . $post_values[2];
                echo "\t" . $post_values[3];
                echo "\t" . $post_values[4];
                echo "\t" . $post_values[5];
                echo "\t" . $post_values[6];
                echo "\t" . $post_values[7];
                echo "\t" . $post_values[8] . "\n";
		$post_group_conc .= $post_values[5] . ",";
//		if ($post_values[6][0] == 'a' && $post_values[6][1] == 'b'){
//	        	$post_group_conc .= $post_values[6] . ",";
//		}
	}
        $post_group_conc = str_replace('ab.', 'alt.binaries.', $post_group_conc);
        $post_group_conc = str_replace('a.b.', 'alt.binaries.', $post_group_conc);
        $post_group_conc = str_replace('abhdtv<br>alt.binaries.town', 'alt.binaries.hdtv', $post_group_conc);
        $post_group_conc = str_replace('abhdtv', 'alt.binaries.hdtv', $post_group_conc);
        //$post_group_conc = str_replace('<br>', '', $post_group_conc);
        $post_group_conc = implode(',',array_unique(explode(',', $post_group_conc)));
	$post_group_array = explode (",", $post_group_conc);
        foreach ( $post_group_array as $post_group_value ) {
		if ($post_group_value != '') {
			$sql_select = 'SELECT active FROM groups WHERE name = \'' . $post_group_value . '\' ;';
			if ( $result = $my_db->query($sql_select) ) {
				if ( $result->num_rows == 1 ) {
					echo "[INFO] Active $post_group_value for $days day(s)\n";
					$sql_update = "UPDATE groups SET active = 1 AND regexmatchonly = 0, backfill_target = $days, description = 'post' WHERE name = '$post_group_value';";
					if ( $my_db->query($sql_update) === FALSE ) { echo "[ERROR]--------->$sql_update<---------\n***".$my_db->error."***\n"; }
				} elseif ( $result->num_rows == 0 ) {
					echo "[INFO] Add and Active $post_group_value for $days day(s)\n";
					$sql_insert = "INSERT INTO groups ( name , backfill_target , active , description, regexmatchonly) VALUES ( '$post_group_value' , $days , 1 , 'post' , 0 ) ;";
					if ( $my_db->query($sql_insert) === FALSE ) { echo "[ERROR]--------->$sql_insert<---------\n***".$my_db->error."***\n"; }
				} else { echo "[ERROR]--------->DUPLICATE GROUP<---------\n"; }
			}else { echo "[ERROR]--------->$sql_select<---------\n***".$my_db->error."***\n"; }
	        $result->close();
		}
	}
	$my_db->close();
}

function add_regex_post( $post_matches) {
	$my_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
        $post_group_conc = "";
	foreach ( $post_matches as $post_values ) {
		// switch to >>$post_group_conc = $post_values[5] . ",";<<
		// for enable regex for all active group instead the specific post group
                $post_group_conc = $post_values[5] . ",";
//                if ($post_values[6][0] == 'a' && $post_values[6][1] == 'b' ){
//			$post_group_conc .= $post_values[6] . ",";
//                }
	        $post_group_conc = str_replace('ab.', 'alt.binaries.', $post_group_conc);
                $post_group_conc = str_replace('a.b.', 'alt.binaries.', $post_group_conc);
                $post_group_conc = str_replace('abhdtv<br>alt.binaries.town', 'alt.binaries.hdtv', $post_group_conc);
                $post_group_conc = str_replace('abhdtv', 'alt.binaries.hdtv', $post_group_conc);
                //$post_group_conc = str_replace('<br>', '', $post_group_conc);
	        $post_group_conc = implode(',',array_unique(explode(',', $post_group_conc)));
		foreach( explode(',',$post_group_conc) as $post_group ) {
			if ( $post_group != '' ) {
				$sql_select = 'SELECT name FROM groups WHERE active = 1 AND name = \'' . $post_group . '\';';
				if ( $result = $my_db->query($sql_select) ) {
					if ( $result->num_rows == 1 ) {
						$regex = '.*?(?P<name>' . $my_db->real_escape_string(str_replace('                          ', '', $post_values[7])) . ').*?' ;
		                                $sql_select2 = "SELECT ID FROM releaseregex WHERE groupname = '$post_group' AND regex = '/$regex/i';";
		                                if ( $result2 = $my_db->query($sql_select2) ) {
		                                        if ( $result2->num_rows == 0 ) {
								echo "[INFO] Add and active regex $post_group : >>$regex<< \n";
								$sql_insert = "INSERT INTO releaseregex (groupname, regex , status , description, ordinal, categoryID) VALUES ('$post_group','/$regex/i',1,'post',1,2040) ;";
								if ( $my_db->query($sql_insert) === FALSE ) { echo "[ERROR]--------->$sql_insert<---------\n***".$my_db->error."***\n"; }
							}
						}
					} else { echo "[ERROR]--------->$post_group DISABLE OR DUPLICATE GROUP<---------\n"; }
					$result->close();
				} else { echo "[ERROR]--------->$sql_select<---------\n***".$my_db->error."***\n"; }
			}
		}
	}
	$my_db->close();
}

function update_release_post( $post_matches ) {
	$my_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	foreach( $post_matches as $post_values ) {
                $post_group_conc = $post_values[5] . ",";
                //if ($post_values[6] != ''){
                //        $post_group_conc .= $post_values[6] . ",";
                //}
                $post_group_conc = str_replace('ab.', 'alt.binaries.', $post_group_conc);
                $post_group_conc = implode(',',array_unique(explode(',', $post_group_conc)));
                foreach( explode(',',$post_group_conc) as $post_group ) {
                        if ( $post_group != '' ) {
				$regex = '.*?(?P<name>' . $my_db->real_escape_string(str_replace('                          ', '', $post_values[7])) . ').*?' ;
                                $sql_select = "SELECT ID FROM releaseregex WHERE groupname = '$post_group' AND description = 'post' AND regex = '/$regex/i';";
				//echo $sql_select . "\n";
		                if ( $result = $my_db->query($sql_select) ) {
		                        if ( $result->num_rows == 1 ) {
		                                while( $row = $result->fetch_object() ) {
		                                        $regexID = $row->ID;
			                                $sql_select2 = "SELECT guid,imdbID FROM releases WHERE regexID = $regexID;";
		        	                        //echo $sql_select2 . "\n";
		                        	        if ( $result2 = $my_db->query($sql_select2) ) {
		                	                        if ( $result2->num_rows > 0 ) {
		                                        	        while( $row2 = $result2->fetch_object() ) {
										//get password via nfo
										$nfo = $row2->guid;
										$arrContextOptions=array(
										    "ssl"=>array(
											    "verify_peer"=>false,
											    "verify_peer_name"=>false,
										    ),
										);
									        $nfo_get = file_get_contents('https://127.0.0.1:8443/api?t=getnfo&apikey=5d4c056b62799e6cbe21b6bea4ac88ab&id='.$nfo, false, stream_context_create($arrContextOptions));
									        //echo "$nfo_get\n\n";
									        $pwd_regex = '/.*pw : (.*)/';
										preg_match_all( $pwd_regex , $nfo_get , $pwd_matches, PREG_SET_ORDER);
										$pwd = '';
									        foreach( $pwd_matches as $pwd_values ) {
											$pwd = $pwd_values[1];
											//micmac caractere de fin incorrect
											$i = strlen($pwd) - 1;
                	                                                                $pwd[$i] = "\n";
											$pwd = str_replace("\n",'', $pwd);
									                $pwd = '{{' . $pwd . '}}';
										}
										//get password via Binnews Info
										if ($pwd == ''){
											$nfo_get = file_get_contents('https://www.binnews.ninja/nfo.php?link='.$post_values[8]);
											//echo $nfo_get . "\n";
        	                                                                        $pwd_regex = '/.*pw : (.*)</';
											preg_match_all( $pwd_regex , $nfo_get , $binnews_matches, PREG_SET_ORDER);
               	        	                                                        foreach( $binnews_matches as $binnews_values ) {
												$pwd = $binnews_values[1];
                                        	                                                $pwd = '{{' . $pwd . '}}';
											}
	                                                                                if ($pwd == ''){
												$pwd_regex = '/.*ot de passe.?: (.*?)</';
        	                                                        	                preg_match_all( $pwd_regex , $nfo_get , $binnews_matches, PREG_SET_ORDER);
                	                                                        	        foreach( $binnews_matches as $binnews_values ) {
	                	                                                        	        $pwd = $binnews_values[1];
													if ($pwd == 'Nom du fichier') {
	                                	                                                        	$pwd = '{{' . $post_values[7] . '}}';
													}else{
														$pwd = '{{' . $pwd . '}}';
													}
	                                	                                                }
											}
										}
										//check imdb
                        	                                                $searchname = $my_db->real_escape_string($post_values[2] . " (" . $post_values[4] . ") " . $pwd);
										if ($row2->imdbID == '0000000') {
											$imdb = correct_imdb($post_values[2] . ' ' .$post_values[4]);
	                                	                                        $sql_update2 = "UPDATE releases SET categoryID = '2040', imdbID = '$imdb', searchname = '$searchname' WHERE regexID = $regexID;";
                                                                                        if ($imdb == '0000000') {
		                                        	                                echo "[INFO] No imdb found ! But update release $regex with category 2040 >>> name $searchname >>> imdbid = $imdb\n";
											}else {
												echo "[INFO] Update release $regex with category 2040 >>> name $searchname >>> imdbid = $imdb\n";
											}
										}else {
			                                        	       		$sql_update2 = "UPDATE releases SET categoryID = '2040', searchname = '$searchname' WHERE regexID = $regexID;";
	                                                                	        echo "[INFO] Update release $regex with category 2040 >>> name $searchname \n";
										}
				                                                if ( $my_db->query($sql_update2) === FALSE ) { echo "[ERROR]--------->$sql_update2<---------\n***".$my_db->error."***\n"; }
										//send NZB file to SABnzbd
										$sabdone = file_get_contents('./sabdone');
										if (strstr($sabdone, $nfo) == false) {
											$cmd = 'cp /mnt/nfs/newznab/nzbfiles/' . $nfo[0] . '/' . $nfo . '.nzb.gz "/mnt/nfs/sabnzbd/blackhole/' . $post_values[2] . ' ' . $post_values[4] . ' ' . $pwd . '.nzb.gz"';
											echo "[INFO] --- CP NZB --- " . $cmd . "\n";
                                                                                        exec($cmd);
                	                                                                $cmd = 'echo "' . $nfo . '," >> sabdone';
                        	                                                        echo "[INFO] --- NO REDO --- " . $cmd . "\n";
											exec($cmd);
										}
									}
								} // elseif ( $result2->num_rows > 1 ){echo "[ERROR] duplicate regexID::$regexID\n";}
							} else { echo "[ERROR]--------->$sql_update<---------\n***".$my_db->error."***\n"; }
						}
					} else {echo "[WARN] disable or duplicate regex $sql_select \n";}
				}
			}
		}
	}
	$my_db->close();
}

function correct_imdb( $release_name ) {
	$imdb_url = 'https://imdb-api.com/en/API/SearchMovie/k_92dtzqa5/' . str_replace('+', ' ', urlencode($release_name));
	$imdb_result = file_get_contents($imdb_url);
	echo "[INFO] IMDB API CALL :: " . $imdb_url . "\n";
	$imdb_regex='/"id":"tt(.*?)"/';
	if (preg_match_all( $imdb_regex , $imdb_result , $imdb_matches , PREG_SET_ORDER )) {
		return $imdb_matches[0][1];
	} else {return '0000000';}
}

function disable_group_all() {
	$my_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	echo "[INFO] Disable all group...\n";
	$sql_update="UPDATE groups SET active = 0 ;";
	if ($my_db->query($sql_update) === FALSE) { echo "[ERROR]--------->$sql_update<---------\n***".$my_db->error."***\n"; }
	$my_db->close();
}

?>
