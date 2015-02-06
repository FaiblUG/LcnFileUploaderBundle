var LcnFileUploader = (function($) {

  var
    fileTemplate = _.template($.trim($('#file-uploader-file-template').html())),
    uploaderTemplate = _.template($.trim($('#file-uploader-template').html()))
    ;

  function LcnFileUploader(options) {
    this.viewUrl = options.viewUrl;
    this.uploadUrl = options.uploadUrl;
    this.originalFolderName = options.originalFolderName;
    this.thumbnailFolderName = options.thumbnailFolderName;

    this.$el = $(options.el);
    this.$el.addClass('lcn-file-uploader');
    this.$el.data('lcn-file-uploader', this);

    this.$el.html(uploaderTemplate({
      multiple: (!options.maxNumberOfFiles || options.maxNumberOfFiles > 1)
    }));

    this.$errorMessage = this.$el.find('[data-role="error-message"]');
    this.$thumbnails = this.$el.find('[data-role="thumbnails"]');
    this.$add = this.$el.find('[data-action="add"]');

    this.positionInputOverlay(this.$add);

    this.uploading = false;


    if (options.existingFiles) {
      this.addExistingFiles(options.existingFiles);
    }

    this.$el
      .on('fileuploadadd', function (e, data) {
        if (this.getUploadMode() === 'replace') {
          this.showLoadingIndicator();
          this.deleteFile(this.$replaceFileOnUpload).then(function() {
            data.submit();
          });
          this.$replaceFileOnUpload = null;
        }
        else {
          data.submit();
        }
      }.bind(this))
      .on('fileuploadstart', function (e) {
        this.showLoadingIndicator();
        this.hideErrors();
        this.uploading = true;
      }.bind(this))
      .on('fileuploadstop', function (e) {
        this.hideLoadingIndicator();
        this.uploading = false;
      }.bind(this))
      .on('fileuploaddone', function (e, data) {
        if (data && data.result && data.result.files) {
          _.each(data.result.files, function (file) {
            this.appendEditableImage(file);
          }.bind(this));
        }
      }.bind(this))
      .on('fileuploadfail', this.showErrors.bind(this))
    ;

    this.$el.on('mouseenter', '[data-action="replace"], [data-action="add"], [data-role="file-input-wrapper"]', function (e) {
      $trigger = $(e.currentTarget);
      if ($trigger.attr('data-role') === 'file-input-wrapper') {
        //no special action needed
      }
      else if ($trigger.attr('data-action') === 'replace') {
        var $file = $trigger.closest('[data-name]');
        this.$replaceFileOnUpload = $file;
      }
      else {
        this.$replaceFileOnUpload = null;
      }

      this.positionInputOverlay($trigger);
    }.bind(this));

    var blueImpOptions = $.extend({}, {
      dataType: 'json',
      url: this.uploadUrl,
      dropZone: this.$el.find('[data-role="dropzone"]'),
      autoUpload: false
    }, options.blueImpOptions);

    this.getFileInputElement().fileupload(blueImpOptions);

  }

  LcnFileUploader.prototype = {

    getUploadMode: function() {
      if (this.$replaceFileOnUpload) {
        return 'replace';
      }

      return 'add';
    },

    getFileInputElement: function() {
      return this.$el.find('input[type="file"]');
    },

    getFileInputWrapper: function() {
      return this.getFileInputElement().closest('[data-role="file-input-wrapper"]');
    },

    positionInputOverlay: function($el) {

      if (this.positionInputOverlayTimeout) {
        clearTimeout(this.positionInputOverlayTimeout);
      }

      var $fileInputWrapper = this.getFileInputWrapper();

      if (this.getUploadMode() === 'replace') {
        $fileInputWrapper.addClass('mode-replace');
        $fileInputWrapper.removeClass('mode-add');
      }
      else {
        $fileInputWrapper.addClass('mode-add');
        $fileInputWrapper.removeClass('mode-replace');
      }

      $fileInputWrapper.css({
        left: $el.position().left,
        top: $el.position().top,
        width: $el.outerWidth(),
        height: $el.outerHeight()
      });


      this.positionInputOverlayTimeout = setTimeout(this.positionInputOverlay.bind(this, $el), 500);
    },

    showLoadingIndicator: function() {
      this.$el.addClass('loading');
    },

    hideLoadingIndicator: function() {
      this.$el.removeClass('loading');
    },

    // Delay form submission until upload is complete.
    // Note that you are welcome to examine the
    // uploading property yourself if this isn't
    // quite right for you
    delaySubmitWhileUploading: function (sel) {
      $(sel).submit(function (e) {
        if (!this.uploading) {
          return true;
        }

        function attempt() {
          if (this.uploading) {
            setTimeout(attempt.bind(this), 100);
          }
          else {
            $(sel).submit();
          }
        }

        attempt();

        return false;
      }.bind(this));
    },

    addExistingFiles: function (files) {
      _.each(files, function (file) {
        this.appendEditableImage({
          // cmsMediaUrl is a global variable set by the underscoreTemplates partial of MediaItems.html.twig
          'thumbnailUrl': /(\.png|\.jpg|\.jpeg|\.gif)$/i.test(file) ? this.viewUrl + '/'+this.thumbnailFolderName+'/' + file : undefined,
          'url': this.viewUrl + '/'+this.originalFolderName+'/' + file,
          'name': file
        });
      }.bind(this));
    },

    // Expects thumbnail_url, url, and name properties. thumbnail_url can be undefined if
    // url does not end in gif, jpg, jpeg or png. This is designed to work with the
    // result returned by the UploadHandler class on the PHP side
    appendEditableImage: function (info) {
      if (info.error) {
        this.showErrors(info);

        return;
      }

      var li = $(fileTemplate(info));
      li.find('[data-action="delete"]').click(function (e) {
        this.hideErrors();
        var $file = $(e.currentTarget).closest('[data-name]');
        this.deleteFile($file);
        e.preventDefault();
      }.bind(this));

      this.$thumbnails.append(li);
    },

    deleteFile: function($file) {
      var name = $file.attr('data-name');
      return $.ajax({
        type: 'delete',
        url: this.setQueryParameter(this.uploadUrl, 'file', name),
        success: function () {
          $file.remove();
        },
        dataType: 'json'
      });
    },

    setQueryParameter: function (url, param, paramVal) {
      var newAdditionalURL = "";
      var tempArray = url.split("?");
      var baseURL = tempArray[0];
      var additionalURL = tempArray[1];
      var temp = "";
      if (additionalURL) {
        var tempArray = additionalURL.split("&");
        var i;
        for (i = 0; i < tempArray.length; i++) {
          if (tempArray[i].split('=')[0] != param) {
            newAdditionalURL += temp + tempArray[i];
            temp = "&";
          }
        }
      }
      var newTxt = temp + "" + param + "=" + encodeURIComponent(paramVal);
      var finalURL = baseURL + "?" + newAdditionalURL + newTxt;
      return finalURL;
    },

    showErrors: function(info) {
      this.$errorMessage.text(info.error).show();
      this.$el.trigger('lcn-file-uploader:error', info)
    },

    hideErrors: function() {
      this.$errorMessage.hide();
    }
  };

  return LcnFileUploader;
})(jQuery);