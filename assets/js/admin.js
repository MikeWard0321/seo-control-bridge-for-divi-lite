(function () {
  function updateCounter(field) {
    var counter = field.parentNode.querySelector('.seo-control-bridge-lite-counter');
    if (!counter) {
      counter = document.createElement('span');
      counter.className = 'seo-control-bridge-lite-counter description';
      field.parentNode.appendChild(counter);
    }
    counter.textContent = 'Characters: ' + field.value.length;
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#seo_control_bridge_lite_description, #seo_control_bridge_lite_facebook_description, #seo_control_bridge_lite_twitter_description').forEach(function (field) {
      updateCounter(field);
      field.addEventListener('input', function () { updateCounter(field); });
    });
  });
})();
