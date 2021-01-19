$(document).ready(function () {
	window.setTimeout(function () {
		updateLive();
		setInterval(() => { updateLive(); }, 10000);
	}, 500);


	window.setTimeout(function () {
		updateToday();
		setInterval(() => { updateToday(); }, 30000);
	}, 700);


	window.setTimeout(function () {
		showGraphs();
	}, 1000);
});

function createGraph(chart, title, containerId) {
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

	let element = document.getElementById(containerId);

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

			window.setTimeout(function () {
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
			}, 500);
		});
}

function updateToday() {
	$.post("Today.php",
		function (db) {
			if (!db) {
				return;
			}

			window.setTimeout(function () {
				const fiveSecondDay = [];
				for (var i = 0; i <= 86400; i = i + 5) {
					fiveSecondDay.push(i);
				}
				let chartdata = {
					labels: fiveSecondDay.map((v, index) => Math.round(v / 60 / 60 * 100) / 100),
					datasets: [
						{
							label: 'produced (' + Math.round(Object.values(db).reduce((a, b) => a + b) / 12) / 1000 + 'kW)',
							backgroundColor: "rgba(81,153,68,1)",
							borderColor: "rgba(81,153,68,0.4)",
							fill: true,
							pointRadius: 1,
							borderWidth: 2,
							showLine: true,
							spanGaps: true,
							data: fiveSecondDay.map((v, index) =>
								(db.hasOwnProperty(v + '')
									&& db[v] / 1000) || undefined
							),
						}
					]
				};

				createGraph({
					type: 'line',
					data: chartdata,
					options: {
						responsive: true,
						maintainAspectRatio: false,
						animation: {
							duration: 0
						},
						scales: {
							xAxes: [{
								ticks: {
									steps: 25,
									stepValue: 1,
									max: 24,
									min: 0,
								}
							}],
							yAxes: [{
								ticks: {
									steps: 11,
									stepValue: 1,
									max: 10.5,
									min: 0,
								}
							}]
						}
					}
				}, "Today", "today-container");
			}, 500);
		});
}

function showGraph(dbs, n) {
	window.setTimeout(function () {
		if (!dbs || n < 0) {
			return;
		}
		const db = dbs[n];

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
				responsive: true,
				maintainAspectRatio: false,
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


		showGraph(dbs, n - 1);
	}, 500 * (dbs.length - n));
}



function showGraphs() {
	{
		$.post("Data.php",
			function (dbs) {
				showGraph(dbs, dbs.length - 1);
			});
	}
}