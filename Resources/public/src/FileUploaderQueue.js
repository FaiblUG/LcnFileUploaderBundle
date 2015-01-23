window.lcn_file_uploader_queue = (function($, window) {
  var queue = {
    push: function(data) {
      new LcnFileUploader(data);
    }
  };

  if (window.lcn_file_uploader_queue) {
    for (var i = 0; i < window.lcn_file_uploader_queue.length; i++) {
      if (window.lcn_file_uploader_queue.hasOwnProperty(i)) {
        queue.push(window.lcn_file_uploader_queue[i]);
      }
    }
  }

  return queue;
})(jQuery, window);
