<style>
.carrierTooltip {
  position: absolute;
  display: none;
  pointer-events: none;
  background: #fff;
  padding: 5px;
  text-align: left;
  border: solid #ccc 1px;
  color: #666;
  font-size: 14px;
  font-family: sans-serif;
}
</style>
<svg id="carrierInfo" width="960" height="400"></svg>
<div id="ca"></div>
<script>

function calculateCarrierInfo(data){
  var carriers = [];
  var barchartdata = [];
  var origins = [];
    
  for(var i = 0; i < data.length; i++){
    var d = data[i];
    
    if(carriers.indexOf(d.carrier) == -1){
      carriers.push(d.carrier);
      var carrier_array = [];
      carrier_array.carrier = d.carrier;
      barchartdata.push(carrier_array);
    }

    if(origins.indexOf(d.origin) == -1){
      origins.push({o: d.origin, c: d.origin_city_name, total: parseFloat(d.total)});
    }
  }

  for(var i = 0; i < barchartdata.length; i++){
    for(var j = 0; j < origins.length; j++){
      barchartdata[i].push({
        x: origins[j].o,
        y: 0,
        carrier: barchartdata[i].carrier,
        city: origins[j].c,
        total: origins[j].total,
      })
    }
  }

  for(var i = 0; i < data.length; i++){
    var d = data[i];
    for(var j = 0; j < barchartdata.length; j++){
      if(barchartdata[j].carrier == d.carrier){
        for(var m = 0; m < barchartdata[j].length; m++){
          if(barchartdata[j][m].x == d.origin){
            barchartdata[j][m].y = parseFloat(d.c);
          }
        }
      }
    }
  }

  barchartdata.columns = carriers;
  barchartdata.origins = origins;
  return barchartdata;
}

var Carrier = function(){
  var svg = d3.select("#carrierInfo");
  var margin = {top: 20, right: 20, bottom: 30, left: 40};
  var width = +svg.attr("width") - margin.left - margin.right;
  var height = +svg.attr("height") - margin.top - margin.bottom;
  var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");
  var carrierTooltip = d3.select("#ca").append("div").attr("class", "carrierTooltip");
  var x = d3.scale.ordinal().rangeRoundBands([0, width], .05);
  var y = d3.scale.linear().rangeRound([height, 0]);

  z = d3.scale.ordinal().range(["#e6f2ff", "#cce6ff", "#b3d9ff", "#99ccff", "#80bfff", "#66b3ff", "#4da6ff","#3399ff","#1a8cff","#0073e6","#004d99","#001a33"].reverse());

  var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

  var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left")
    .ticks(null, "s");

  this.recolorbar = function(d){
    return z(d);
  }

  this.redrawBarChart = function(data){
    g.selectAll('g').remove();
    g.selectAll('.axis').remove();

    var data = calculateCarrierInfo(data);
    var keys = data.columns;

    var layers = d3.layout.stack()(data);
    x.domain(layers[0].map(function(d) { return d.x; }));
    y.domain([0, d3.max(data.origins, function(d){return d.total;})]).nice();
    z.domain(keys);

    g.append("g")
    .selectAll("g")
    .data(d3.layout.stack()(layers))
    .enter().append("g")
      .attr("fill", function(d) {return z(d.carrier); })
    .selectAll("rect")
    .data(function(d) { return d; })
    .enter().append("rect")
      .attr("x", function(d) {return x(d.x); })
      .attr("y", function(d) {return y(d.y + d.y0); })
      .attr("height", function(d) { return y(d.y0) - y(d.y + d.y0); })
      .attr("width", x.rangeBand())
      .attr("class","carrierbar")
      .on("mouseover",function(d){
        carrierTooltip.text(d.x +" ("+ d.city +"): " +d.carrier + " - " + d.y.toLocaleString() + " / " + d.total.toLocaleString() + " flights")
          .style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px")
          .style("display", "block")
          .style("opacity", 1);

        d3.selectAll("circle").each(function(){
          var f = d3.select(this);
          if(f.datum().airport == d.x){
            f.attr("stroke","blue");
          }
        });
      })
      .on("mouseout",function(d){
        d3.selectAll("circle").each(function(){
          var f = d3.select(this);
          if(f.datum().airport == d.x){
            f.attr("stroke",null);
          }
        });
      });

    g.append("g")
      .attr("class", "axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis)
        .selectAll("text")  
        .style("text-anchor", "end")
        .attr("font-size", 11)
        .attr("dx", "-.8em")
        .attr("dy", "-.15em")
        .attr("transform", function(d) {
            return "rotate(-65)" 
            });

    g.append("g")
        .attr("class", "axis")
        .call(yAxis)
      .append("text")
        .attr("x", 2)
        .attr("y", y(y.ticks().pop()) + 0.5)
        .attr("dy", "0.32em")
        .attr("fill", "#000")
        .attr("font-weight", "bold")
        .attr("text-anchor", "start")
        .text("Flights by carrier");

    var legend = g.append("g")
        .attr("font-family", "sans-serif")
        .attr("font-size", 10)
        .attr("text-anchor", "end")
      .selectAll("g")
      .data(keys.slice().reverse())
      .enter().append("g")
        .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

    legend.append("rect")
        .attr("x", width - 19)
        .attr("width", 19)
        .attr("height", 19)
        .attr("fill", z)
        .on("mouseover",function(d){
           d3.selectAll('.carrierbar').each(function(x){
            if(x.carrier == d){
              d3.select(this).style("fill","red"); 
            }
          });
        })
        .on("mouseout",function(d){
           d3.selectAll('.carrierbar').each(function(x){
            if(x.carrier == d){
              d3.select(this).style("fill",z(d)); 
            }
          });
        });

    legend.append("text")
        .attr("x", width - 24)
        .attr("y", 9.5)
        .attr("dy", "0.32em")
        .text(function(d) { return d; });
  }
}

var ci = new Carrier();
d3.json("http://localhost/flightviz/controllers/query.php?q=compute-flight-carriers-for-all-airports", function(error, data) {
  ci.redrawBarChart(data);
});

ci.airportsUpdated = function(airports, prop){
  airports_str = airports.map(function(x){return x[prop]}).join(",");

  d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=compute-flight-carriers-airports&a="+airports_str, function(error, data) {
    ci.redrawBarChart(data);
  });
}

ci.globeAirportHovered = function(airport){
  d3.selectAll('.carrierbar').each(function(d){
    if(d.x == airport.airport){
      d3.select(this).style("stroke","red");
    }
  });
}

ci.globeAirportHoverCancelled = function(airport){
  d3.selectAll('.carrierbar').each(function(d){
    if(d.x == airport.airport){
      d3.select(this).style("stroke",null);
    }
  });
}

ci.globeCarrierHovered = function(carrier, airport){
  d3.selectAll('.carrierbar').each(function(d){
    if(d.carrier == carrier){
      if(d.x == airport){
        d3.select(this).style("fill","yellow");   
      } else{
        d3.select(this).style("fill","red");   
      }
    }
  });
}

ci.globeCarrierCancelled = function(carrier){
  d3.selectAll('.carrierbar').each(function(d){
    if(d.carrier == carrier){
      d3.select(this).style("fill",ci.recolorbar(carrier)); 
    }
  });
}
</script>