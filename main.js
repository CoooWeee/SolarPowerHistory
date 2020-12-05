$(document).ready(function () {
	showGraphs();


	updateLive();
	setInterval(() => { updateLive(); }, 5000);

	updateToday();
	setInterval(() => { updateToday(); }, 30000);
});

function createGraph(chart, title, contrainerId) {
	let container = document.createElement("div");
	const htmlId = "output" + title;
	container.setAttribute("id", htmlId);

	let para = document.createElement("p");
	let node = document.createTextNode(title);
	para.appendChild(node);
	container.appendChild(node);

	let canvas = document.createElement("canvas");
	canvas.setAttribute("id", "canvas" + title);
	container.appendChild(canvas);

	let element = document.getElementById(contrainerId);

	// today might already exist
	if (document.contains(document.getElementById(htmlId))) {
		document.getElementById(htmlId).remove();
	}

	element.appendChild(container);


	let graphTarget = $("#canvas" + title);

	return new Chart(graphTarget, chart);
}

function updateLive() {
	$.post("Live.php",
		function (db) {
			if (!db) {
				return;
			}
			const kw = db["PAC"]["Values"]["1"] / 1000;
			let chartdata = {
				labels: ['Live'],
				datasets: [
					{
						label: kw + 'kW',
						backgroundColor: '#519944',
						data: [kw],
					}
				]
			};
			createGraph({
				type: 'horizontalBar',
				data: chartdata,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					animation: {
						duration: 0
					},
					scales: {
						xAxes: [{
							stacked: false,
							ticks: {
								steps: 10,
								stepValue: 1,
								max: 10,
								min: 0,
							}
						}],
						yAxes: [{
							stacked: false,

						}]
					}
				}
			}, "Live", "live-container");
		});
}

function updateToday() {
	$.post("Today.php",
		function (db) {
			if (!db) {
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
			createGraph({
				type: 'bar',
				data: chartdata,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					animation: {
						duration: 0
					},
					scales: {
						xAxes: [{ stacked: true }],
						yAxes: [{
							stacked: true,
							ticks: {
								steps: 10,
								stepValue: 1,
								max: 10,
								min: 0,
							}
						}]
					}
				}
			}, "Today", "today-container");
		});
}

function showGraphs() {
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

					createGraph({
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
										min: -10,
									}
								}]
							}
						}
					}, readDate, "chart-container");
				}
			});
	}
}