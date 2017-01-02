<?php
include_once('db.php');


switch($_GET['q']){
	case 'all-airports':
		allAirports();
		break;
	case 'flights':
		flights();
		break;
	case 'airports':
		airports($_GET['a']);
		break;
	case 'flights-by-airport':
		getFlightsByAirports($_POST['airports']);
		break;
}

function getJSONFromQuery($query){
	
	$result_as_array = [];

	if ($query_result = $GLOBALS['conn']->query($query)) {

	    while($record = $query_result->fetch_array(MYSQL_ASSOC)) {
	            $result_as_array[] = $record;
	    }
	    echo json_encode($result_as_array);
	} else{
		echo "Error: " . $query . "<br>" . $GLOBALS['conn']->error;
	}
}


function flights(){
	getJSONFromQuery(
		" SELECT * FROM flights LIMIT 5"
	);
}

function allAirports(){
	getJSONFromQuery(
		"SELECT id, city, name, latitude, longitude FROM airports"
	);
}


function airports($amount){
	getJSONFromQuery(
		" SELECT city, z.airport, z.total_traffic, z.incoming, z.outgoing, latitude, longitude FROM airports, 
			( SELECT x.airport as airport, (x.c + y.c) as total_traffic, x.c as incoming, y.c as outgoing from
				( SELECT dest as airport, count(dest) as c FROM flights group by dest order by c DESC LIMIT $amount ) as x,
				( SELECT origin as airport, count(origin) as c FROM flights group by origin order by c DESC LIMIT $amount ) as y
				WHERE x.airport = y.airport order by total_traffic DESC
			) as z
		 WHERE z.airport = airports.IATA"
	);
}

function getFlightsByAirports($airports){
	$airports = implode("','",$airports);
	getJSONFromQuery(
		" 	SELECT x.dest as dest, y.dest as dest2, (x.c + y.c) as total_traffic, x.c as incoming, y.c as outgoing from
				( SELECT origin, dest, count(*) as c FROM flights WHERE origin IN ('$airports') and dest IN ('$airports') group by dest, origin ) as x,
				( SELECT origin, dest, count(*) as c FROM flights WHERE origin IN ('$airports') and dest IN ('$airports') group by origin, dest ) as y
		  	WHERE x.dest = y.origin and x.origin = y.dest 
		"
	);
}
