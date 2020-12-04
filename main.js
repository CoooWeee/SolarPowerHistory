$(document).ready(function () {
	showGraphs();
});

function createGraph(chartdata, title, id, onlyPos) {
	let container = document.createElement("div");

	let para = document.createElement("p");
	let node = document.createTextNode(title);
	para.appendChild(node);
	container.appendChild(node);

	let canvas = document.createElement("canvas");
	canvas.setAttribute("id", "canvas" + id);
	container.appendChild(canvas);

	let element = document.getElementById("chart-container");
	element.appendChild(container);


	let graphTarget = $("#canvas" + id);

	let barGraph = new Chart(graphTarget, {
		type: 'bar',
		data: chartdata,
		options: {
			scales: {
				xAxes: [{ stacked: true }],
				yAxes: [{
					stacked: true,
					ticks: {
						steps: 20,
						stepValue: 1,
						max: 10,
						min: onlyPos ? 0 : -10,
					}
				}]
			}
		}
	});
}

function showGraphs() {
	{
		$.post("Live.php",
			function (db) {
				if(!db) {
					return;
				}
				let chartdata = {
					labels: Object.keys(db),
					datasets: [
						{
							label: 'produced (' + Math.round(Object.values(db).reduce((a, b) => a + b)) + 'kW)',
							backgroundColor: '#519944',
							data: Object.values(db),
						}
					]
				};
				createGraph(chartdata, "Today", "Today", true);
			});
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
								label: 'produced (' + Math.round(db["sum"]["produced"]) + 'kW)',
								backgroundColor: '#519944',
								data: produced,
							},
							{
								label: 'bought (' + Math.round(db["sum"]["bought"]) + 'kW)',
								backgroundColor: '#EC1B14',
								data: bought,
							},
							{
								label: 'estimated consumption (' + Math.round(db["sum"]["consumed"]) + 'kW)',
								backgroundColor: '#DCDB46',
								data: consumed,
							},
							{
								label: 'sold (' + Math.round(db["sum"]["sold"]) + 'kW)',
								backgroundColor: '#2482E8',
								data: sold,
							},
						]
					};

					createGraph(chartdata, readDate, n);
				}
			});
	}
}