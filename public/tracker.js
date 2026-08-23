(function () {
  'use strict';

  if (navigator.doNotTrack === '1') return;

  var script = document.currentScript;
  var apiBase = script.src.replace(/\/tracker\.js.*$/, '');
  var domain = script.getAttribute('data-site') || location.hostname;
  var pageStart = Date.now();
  var currentPath = null;

  function utmParam(name) {
    return new URLSearchParams(location.search).get(name) || undefined;
  }

  function send(path, body) {
    fetch(apiBase + path, {
      method: 'POST',
      keepalive: true,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).catch(function () {});
  }

  function sendBeacon(path, body) {
    var blob = new Blob([JSON.stringify(body)], { type: 'application/json' });
    if (!navigator.sendBeacon || !navigator.sendBeacon(apiBase + path, blob)) {
      send(path, body);
    }
  }

  function trackPageview() {
    currentPath = location.pathname;
    pageStart = Date.now();

    send('/api/collect', {
      domain: domain,
      pathname: currentPath,
      referrer: document.referrer || undefined,
      utm_source: utmParam('utm_source'),
      utm_medium: utmParam('utm_medium'),
      utm_campaign: utmParam('utm_campaign'),
    });
  }

  function reportDuration() {
    if (!currentPath) return;
    var duration = Math.round((Date.now() - pageStart) / 1000);
    if (duration < 1) return;

    sendBeacon('/api/collect/duration', {
      domain: domain,
      pathname: currentPath,
      duration: duration,
    });
  }

  // Single-page apps: treat client-side route changes as new pageviews.
  ['pushState', 'replaceState'].forEach(function (method) {
    var original = history[method];
    history[method] = function () {
      var result = original.apply(this, arguments);
      if (location.pathname !== currentPath) {
        reportDuration();
        trackPageview();
      }
      return result;
    };
  });
  window.addEventListener('popstate', function () {
    if (location.pathname !== currentPath) {
      reportDuration();
      trackPageview();
    }
  });

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') reportDuration();
  });
  window.addEventListener('pagehide', reportDuration);

  trackPageview();
})();
