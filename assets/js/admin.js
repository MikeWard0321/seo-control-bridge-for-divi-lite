(function () {
  function updateCounter(field) {
    var counter = field.parentNode.querySelector('.scbd-lite-counter');
    if (!counter) {
      counter = document.createElement('span');
      counter.className = 'scbd-lite-counter description';
      field.parentNode.appendChild(counter);
    }
    counter.textContent = 'Characters: ' + field.value.length;
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#scbd_lite_description, #scbd_lite_facebook_description, #scbd_lite_twitter_description').forEach(function (field) {
      updateCounter(field);
      field.addEventListener('input', function () { updateCounter(field); });
    });
  });
})();
