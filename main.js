$(document).ready(function () {
	showGraph();
});


function showGraph() {
	{
		$.post("Data.php",
			function (dbs) {
				for (let n = dbs.length - 1; n >= 0; n--) {
					let db = dbs[n];


					// console.log(data);
					let interval = [];
					let bought = [];
					let sold = [];
					let produced = [];
					let consumed = [];

					let readDate = db["date"];
					console.log(readDate);
					const data = db["data"];

					for (let i in data) {
						interval.push(data[i]["interval"]);
						bought.push(data[i]["bought"]);
						sold.push(data[i]["sold"]);
						produced.push(data[i]["produced"]);
						consumed.push(data[i]["consumed"]);
					}

					let chartdata = {
						labels: interval,
						datasets: [
							{
								label: 'produced',
								backgroundColor: '#519944',
								data: produced,
							},
							{
								label: 'bought',
								backgroundColor: '#EC1B14',
								data: bought,
							},
							{
								label: 'estimated consumption',
								backgroundColor: '#DCDB46',
								data: consumed,
							},
							{
								label: 'sold',
								backgroundColor: '#2482E8',
								data: sold,
							},
						]
					};


					let container = document.createElement("div");

					let para = document.createElement("p");
					let node = document.createTextNode(readDate);
					para.appendChild(node);
					container.appendChild(node);

					let canvas = document.createElement("canvas");
					canvas.setAttribute("id", "canvas" + n);
					container.appendChild(canvas);

					let element = document.getElementById("chart-container");
					element.appendChild(container);


					let graphTarget = $("#canvas" + n);

					let barGraph = new Chart(graphTarget, {
						type: 'bar',
						data: chartdata,
						options: {
							scales: {
								xAxes: [{ stacked: true }],
								yAxes: [{
									stacked: true,
									// set this to your max kW (my is 10kW)
									// ticks: {
									// 	steps: 20,
									// 	stepValue: 1,
									// 	max: 10,
									// 	min: -10,
									// }
								}]
							}
						}
					});
				}
			});
	}
}