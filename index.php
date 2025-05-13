<?php
$boxId = '652cf6387fca45000887d4ab';
$apiUrl = "https://api.opensensemap.org/boxes/$boxId";

$response = file_get_contents($apiUrl);
$boxData = json_decode($response, true);

$boxName = $boxData['name'] ?? 'Unknown Box';
$location = $boxData['currentLocation'] ?? [0, 0];
$latitude = $location[1];
$longitude = $location[0];
$sensors = $boxData['sensors'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($boxName) ?> - Sensor Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    #map { height: 300px; }
    .countdown { font-weight: bold; color: #0d6efd; }
  </style>
</head>
<body>
<div class="container py-5">
  <h1 class="mb-3"><?= htmlspecialchars($boxName) ?> - Sensor Dashboard</h1>
  <p class="text-muted mb-1">Country: <strong>Norway</strong></p>
  <p><strong>Latitude:</strong> <?= $latitude ?>, <strong>Longitude:</strong> <?= $longitude ?></p>

  <h2 class="mt-4">Map Location</h2>
  <div id="map" class="mb-5 rounded shadow-sm"></div>

  <h2 class="mt-4">Sensor Cards</h2>
  <div class="row" id="sensorCards">
    <?php foreach ($sensors as $sensor): ?>
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($sensor['title']) ?></h5>
            <p class="card-text">
              <strong>Last Value:</strong>
              <span class="sensor-value" data-sensor-id="<?= $sensor['_id'] ?>">
                <?= htmlspecialchars($sensor['lastMeasurement']['value'] ?? 'N/A') ?>
              </span>
              <?= htmlspecialchars($sensor['unit'] ?? '') ?>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="mt-4">Live Sensor Data Chart <span class="countdown" id="countdown">(refreshes in 60s)</span></h2>
  <canvas id="sensorChart" class="mb-5"></canvas>
</div>

<script>
const latitude = <?= json_encode($latitude) ?>;
const longitude = <?= json_encode($longitude) ?>;
const boxId = <?= json_encode($boxId) ?>;

// Initialize Leaflet map
const map = L.map('map').setView([latitude, longitude], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
}).addTo(map);
L.marker([latitude, longitude]).addTo(map)
  .bindPopup(`Lat: ${latitude}<br>Lng: ${longitude}`)
  .openPopup();
setTimeout(() => { map.invalidateSize(); }, 200);

// Chart.js sensor graph
let chart;
function fetchSensorData() {
  fetch(`https://api.opensensemap.org/boxes/${boxId}`)
    .then(res => res.json())
    .then(data => {
      const labels = [];
      const values = [];

      data.sensors.forEach(sensor => {
        const title = sensor.title;
        const value = parseFloat(sensor.lastMeasurement?.value || 0);
        const unit = sensor.unit || '';

        // Chart data
        labels.push(title);
        values.push(value);

        // Update cards
        const el = document.querySelector(`.sensor-value[data-sensor-id="${sensor._id}"]`);
        if (el) {
          el.textContent = value + ' ' + unit;
        }
      });

      if (chart) {
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.update();
      } else {
        const ctx = document.getElementById('sensorChart').getContext('2d');
        chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Latest Sensor Values',
              data: values,
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                title: { display: true, text: 'Value' }
              }
            }
          }
        });
      }
    });
}

// Countdown timer
let counter = 60;
function countdownTimer() {
  const el = document.getElementById('countdown');
  counter--;
  el.textContent = `(refreshes in ${counter}s)`;
  if (counter <= 0) {
    fetchSensorData();
    counter = 60;
  }
}
fetchSensorData();
setInterval(countdownTimer, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
