document.addEventListener('DOMContentLoaded', () => {
	const liveUrl = document.body.dataset.liveUrl;
	const intervalSeconds = Number(document.body.dataset.liveInterval ?? 0);

	if (!liveUrl || !Number.isFinite(intervalSeconds) || intervalSeconds < 10) {
		return;
	}

	const recentPredictionsBody = document.getElementById('recent-predictions-body');
	const recentSensorLogs = document.getElementById('recent-sensor-logs');

	const setText = (selector, value) => {
		const element = document.getElementById(selector);

		if (element) {
			element.textContent = value;
		}
	};

	const setClassState = (selector, addClasses, removeClasses) => {
		const element = document.getElementById(selector);

		if (!element) {
			return;
		}

		element.classList.remove(...removeClasses);
		element.classList.add(...addClasses);
	};

	const renderPredictions = (predictions) => {
		if (!recentPredictionsBody) {
			return;
		}

		if (!predictions.length) {
			recentPredictionsBody.innerHTML = '<tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 sm:px-6">No predictions have been stored yet.</td></tr>';
			return;
		}

		recentPredictionsBody.innerHTML = predictions.map((prediction) => `
			<tr class="hover:bg-white/5">
				<td class="px-5 py-4 sm:px-6">
					<img src="${prediction.image}" alt="Prediction ${prediction.id}" class="h-14 w-20 rounded-xl object-cover ring-1 ring-white/10">
				</td>
				<td class="px-5 py-4 sm:px-6">
					<div class="font-medium text-white">${prediction.prediction}</div>
					<div class="mt-1 text-slate-500">Prediction #${prediction.id}</div>
				</td>
				<td class="px-5 py-4 sm:px-6 text-emerald-200">${prediction.confidence}</td>
				<td class="px-5 py-4 sm:px-6 text-slate-400">${prediction.createdAt}</td>
			</tr>
		`).join('');
	};

	const renderSensorLogs = (logs) => {
		if (!recentSensorLogs) {
			return;
		}

		if (!logs.length) {
			recentSensorLogs.innerHTML = '<div class="px-5 py-10 text-center text-slate-400 sm:px-6">No sensor logs have been stored yet.</div>';
			return;
		}

		recentSensorLogs.innerHTML = logs.map((log) => `
			<div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
				<div>
					<p class="font-medium text-white">${log.sensor}</p>
					<p class="mt-1 text-sm text-slate-400">${log.timestamp}</p>
				</div>
				${log.raw ? `
					<span class="rounded-full border border-white/10 px-3 py-1 text-sm ${log.tone === 'cyan' ? 'bg-cyan-400/10 text-cyan-200' : 'bg-emerald-400/10 text-emerald-200'}">
						<div class="text-sm">Estimated Nutrient Level</div>
						<div class="mt-1 font-semibold text-white">${log.reading}</div>
						<div class="mt-1 text-xs text-slate-400">raw ${log.raw}</div>
					</span>
				` : `
					<span class="rounded-full border border-white/10 px-3 py-1 text-sm ${log.tone === 'cyan' ? 'bg-cyan-400/10 text-cyan-200' : 'bg-emerald-400/10 text-emerald-200'}">${log.reading}</span>
				`}
			</div>
		`).join('');
	};

	const syncDashboard = async () => {
		try {
			const response = await fetch(liveUrl, {
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (!response.ok) {
				return;
			}

			const payload = await response.json();

			setText('last-synced-at', payload.lastSyncedAt ?? '—');
			setText('latest-prediction-title', payload.latestPrediction?.prediction ?? 'No prediction yet');
			setText('latest-prediction-confidence', payload.latestPrediction?.confidence ?? '0.0%');
			setText('latest-prediction-created-at', payload.latestPrediction?.createdAt ?? 'No data');
			setText('latest-prediction-relative-time', payload.latestPrediction?.relativeTime ?? 'Waiting for upload');
			setText('latest-prediction-diagnosis', payload.latestPrediction?.prediction ?? 'No prediction yet');
			setText('latest-prediction-confidence-inline', payload.latestPrediction?.confidence ?? '0.0%');
			setText('latest-prediction-uploaded-at', payload.latestPrediction?.createdAt ?? 'No data');
			setText('latest-prediction-freshness', payload.latestPrediction?.relativeTime ?? 'Waiting for upload');
			setText('latest-temperature-value-card', payload.latestTemperature?.value ?? '--.-');
			setText('latest-temperature-relative-time-card', payload.latestTemperature?.relativeTime ?? 'No reading yet');
			setText('latest-temperature-created-at-card', payload.latestTemperature?.createdAt ?? 'No data');
			const tdsUncalibrated = Boolean(payload.latestTds?.uncalibrated);
			const tdsCategory = payload.latestTds?.category ?? null;
			const tdsValue = payload.latestTds?.value ?? '--';

			if (tdsUncalibrated) {
				setText('latest-tds-value', tdsCategory ?? tdsValue);
				setText('latest-tds-raw', `raw ${tdsValue}`);
				setText('latest-tds-unit', '');
			} else {
				setText('latest-tds-value', tdsValue);
				setText('latest-tds-raw', '');
				setText('latest-tds-unit', 'ppm');
			}

			setText('latest-tds-relative-time', payload.latestTds?.relativeTime ?? 'No reading yet');
			setText('telemetry-volume-value', payload.overviewCards?.[3]?.value ?? '0 records');
			setText('telemetry-volume-meta', payload.overviewCards?.[3]?.meta ?? 'Latest sync just now');

			const predictionConfidence = Number(payload.latestPrediction?.confidenceValue ?? 0);
			const predictionProgress = document.getElementById('latest-prediction-progress');
			const inlinePredictionProgress = document.getElementById('latest-prediction-inline-progress');

			if (predictionProgress) {
				predictionProgress.style.width = `${predictionConfidence}%`;
			}

			if (inlinePredictionProgress) {
				inlinePredictionProgress.style.width = `${predictionConfidence}%`;
			}

			const latestPredictionImage = document.getElementById('latest-prediction-image');

			if (latestPredictionImage && payload.latestPrediction?.image) {
				latestPredictionImage.src = payload.latestPrediction.image;
			}

			const temperatureWarningText = document.getElementById('temperature-warning-text');
			const temperatureWarningDot = document.getElementById('temperature-warning-dot');
			const tdsWarningText = document.getElementById('tds-warning-text');
			const tdsWarningDot = document.getElementById('tds-warning-dot');

			if (temperatureWarningText && temperatureWarningDot) {
				const hot = Boolean(payload.warningStates?.highTemperature);
				temperatureWarningText.textContent = hot ? 'High temperature warning' : 'Temperature within range';
				temperatureWarningDot.className = hot ? 'h-2 w-2 rounded-full bg-amber-300' : 'h-2 w-2 rounded-full bg-emerald-300';
				setClassState('temperature-warning', hot ? ['text-amber-200'] : ['text-emerald-200'], hot ? ['text-emerald-200'] : ['text-amber-200']);
			}

			if (tdsWarningText && tdsWarningDot) {
				const lowTds = Boolean(payload.warningStates?.lowTds);
				tdsWarningText.textContent = lowTds ? 'Low nutrient warning' : 'Nutrient level acceptable';
				tdsWarningDot.className = lowTds ? 'h-2 w-2 rounded-full bg-rose-300' : 'h-2 w-2 rounded-full bg-emerald-300';
				setClassState('tds-warning', lowTds ? ['text-rose-200'] : ['text-emerald-200'], lowTds ? ['text-emerald-200'] : ['text-rose-200']);
			}

			const overviewValues = document.querySelectorAll('.overview-value');
			const overviewMetas = document.querySelectorAll('.overview-meta');

			if (overviewValues.length >= 4 && overviewMetas.length >= 4) {
				overviewValues[0].textContent = payload.overviewCards?.[0]?.value ?? 'Waiting for image';
				overviewMetas[0].textContent = payload.overviewCards?.[0]?.meta ?? 'No plant image uploaded yet';
				overviewValues[1].textContent = payload.overviewCards?.[1]?.value ?? 'No reading yet';
				overviewMetas[1].textContent = payload.overviewCards?.[1]?.meta ?? 'Waiting for ESP8266 telemetry';
				overviewValues[2].textContent = payload.overviewCards?.[2]?.value ?? 'No reading yet';
				overviewMetas[2].textContent = payload.overviewCards?.[2]?.meta ?? 'Awaiting hydroponic sensor data';
				overviewValues[3].textContent = payload.overviewCards?.[3]?.value ?? '0 records';
				overviewMetas[3].textContent = payload.overviewCards?.[3]?.meta ?? 'Latest sync just now';
			}

			renderPredictions(payload.recentPredictions ?? []);
			renderSensorLogs(payload.recentSensorLogs ?? []);
		} catch (error) {
			console.error('Dashboard live sync failed', error);
		}
	};

	syncDashboard();
	window.setInterval(syncDashboard, intervalSeconds * 1000);
});
