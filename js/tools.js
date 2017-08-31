

/**
 */

/**
 * 
 * @param id
 * @param pColor
 * @param pFill
 * @param pMin
 * @param pMax
 * @param pLabel
 * @param pData
 * @returns
 */
function graph(id, pColor, pFill, pMin,pMax, pLabel, pData, pData2, pColor2) {
	
	timezoneJS.timezone.zoneFileBasePath="tz";
	
    var graph_options = {
		lines: { show: true, fill: pFill, fillColor: "light"+pColor, lineWidth: 1.5},
	   	points: { show: false },
       	xaxis: {
       		show: false
       		//mode : "time", timezone : "browser"
		},
      	yaxis: {
      		min: pMin,
        	max: pMax,
        	tickFormatter: function(label, series) {
        	  	return label + pLabel;
        	}
      	},
      	crosshair: {
			mode: "x"
		},
      	grid: {
      		hoverable: true,
			//autoHighlight: false,
        	borderWidth: {
	          	top: 0,
    	      	right: 0,
        	  	bottom: 0,
          		left: 0
        	}
      	}
    };

    if (pData2) {
    	if (id.match(/convoluer/i)) {
    		label1="Normal";
    		label2="Convoluer";
    	}
    	else {
    		label1="Fichier 1";
    		label2="Fichier 2";
    	}
    }
    else {
    	label1="";
    	label2="";
    }
	var graphData = {
		      'color': pColor,
		      'label': label1,
		      'data': pData
		   	};
	
	var graphData2 = {
		      'color': pColor2,
		      'label': label2,
		      'data': pData2
		   	};

	var plot = $.plot('#'+id, [graphData, graphData2], graph_options);
		    
	var updateLegendTimeout = null;
	var latestPosition = null;
	
    var legends = $("#"+id+" .legendLabel");

	legends.each(function () {
		// fix the widths so they don't jump around
		$(this).css('width', $(this).width());
		
	});

	function updateLegend() {

		updateLegendTimeout = null;

		var pos = latestPosition;
		var axes = plot.getAxes();
		if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
			pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
			return;
		}

		var i, j, dataset = plot.getData();
		for (i = 0; i < dataset.length; ++i) {

			var series = dataset[i];
			
			// Find the nearest points, x-wise
			for (j = 0; j < series.data.length; ++j) {
				if (series.data[j][0] > pos.x) {
					break;
				}
			}

			// Now Interpolate

			var y,
				p1 = series.data[j - 1],
				p2 = series.data[j];

			if (p1 == null) {
				y = p2[1];
			} else if (p2 == null) {
				y = p1[1];
			} else {
				y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
			}
			legends.eq(i).text(series.label=y.toFixed(2));
			//legends.eq(i).text(series.label.replace(/=.*/, "= " + y.toFixed(2)));
		}
	}
	
	$("<div id='tooltip'></div>").css({
		position: "absolute",
		display: "none",
		border: "1px solid #fdd",
		padding: "2px",
		"background-color": "#fee",
		opacity: 0.80,
		"z-index": 150
	}).appendTo("body");
	
	function title(item,pos) {
		if (item) {
		var x = item.datapoint[0].toFixed(2),
			y = item.datapoint[1].toFixed(2);
		
			var dateDelta = item.datapoint[0]-item.series.datapoints.points[0];
			var date = new Date(item.datapoint[0]*1000);
			// 	Hours part from the timestamp
			var hours = date.getHours();
			// Minutes part from the timestamp
			var minutes = "0" + date.getMinutes();
			// 	Seconds part from the timestamp
			var seconds = "0" + date.getSeconds();
			
			$("#tooltip").html("&nbsp;&nbsp;&nbsp;"+ hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2) + ' [Tps:'+ dateDelta +'sec] => ' + y )
				.css({top: item.pageY+5, left: item.pageX+5}).fadeIn(200);
		} else {
				$("#tooltip").hide();
		}
	}
	
	$("#"+id).bind("plothover",  function (event, pos, item) {
/*		latestPosition = pos;
		if (!updateLegendTimeout) {
			updateLegendTimeout = setTimeout(updateLegend, 50);
		}*/
		title(item,pos);
	});
	
}

/**
 * 
 * @param id
 * @returns
 */
function getElement(id) {
	if (document.layers)
	  	  return document[id];
	    else if (document.all)
	  	   return document.all[id];
	     else if (document.getElementById)
	    	return document.getElementById(id);
	
}