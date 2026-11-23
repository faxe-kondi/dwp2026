document.addEventListener('DOMContentLoaded', function () {

  // ---------- dropzone: click-to-browse, drag-and-drop, live preview (upload page) ----------
  var dropzone = document.querySelector('.dropzone');
  if (dropzone) {
    var input = document.getElementById('fileInput');
    var dropzonePreview = document.getElementById('dropzonePreview');
    var fileNameEl = document.getElementById('fileName');
    var submitButton = dropzone.closest('form').querySelector('[type="submit"]');
    var previewUrl = null;
    var selectionId = 0;

    function optimizeImage(file) {
      if (!window.createImageBitmap) {
        return Promise.resolve(file);
      }

      return createImageBitmap(file).then(function (bitmap) {
        var size = 1200;
        var cropSize = Math.min(bitmap.width, bitmap.height);
        var sourceX = Math.floor((bitmap.width - cropSize) / 2);
        var sourceY = Math.floor((bitmap.height - cropSize) / 2);
        var canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        var context = canvas.getContext('2d');
        if (!context) {
          bitmap.close();
          throw new Error('Could not prepare an optimized image.');
        }

        context.fillStyle = '#fff';
        context.fillRect(0, 0, size, size);
        context.drawImage(bitmap, sourceX, sourceY, cropSize, cropSize, 0, 0, size, size);
        bitmap.close();

        return new Promise(function (resolve, reject) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              reject(new Error('Could not encode an optimized image.'));
              return;
            }
            if (blob.type !== 'image/webp') {
              reject(new Error('This browser cannot encode WebP images.'));
              return;
            }
            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', {
              type: 'image/webp',
              lastModified: Date.now()
            }));
          }, 'image/webp', 0.82);
        });
      });
    }

    function updatePreview(file) {
      if (!dropzonePreview) { return; }
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(file);
      dropzonePreview.src = previewUrl;
      dropzonePreview.hidden = false;
      dropzone.classList.add('has-preview');
    }

    function showSelectedFile(file) {
      var currentSelection = ++selectionId;
      if (submitButton) submitButton.disabled = true;
      if (fileNameEl) fileNameEl.textContent = 'Optimizing image…';

      optimizeImage(file).then(function (optimizedFile) {
        if (currentSelection !== selectionId) { return; }
        if (optimizedFile !== file && input) {
          var transfer = new DataTransfer();
          transfer.items.add(optimizedFile);
          input.files = transfer.files;
        }
        updatePreview(optimizedFile);
        if (fileNameEl) {
          fileNameEl.textContent = optimizedFile === file
            ? file.name + ' (will be optimized on upload)'
            : file.name + ' (optimized to square)';
        }
      }).catch(function (error) {
        if (currentSelection !== selectionId) { return; }
        console.error('Image optimization failed:', error);
        updatePreview(file);
        if (fileNameEl) fileNameEl.textContent = file.name + ' (optimization unavailable)';
      }).then(function () {
        if (currentSelection === selectionId && submitButton) {
          submitButton.disabled = false;
        }
      });
    }

    dropzone.addEventListener('click', function () {
      if (input) input.click();
    });

    if (input) {
      input.addEventListener('change', function () {
        if (input.files && input.files[0]) showSelectedFile(input.files[0]);
      });
    }

    ['dragover', 'dragenter'].forEach(function (evt) {
      dropzone.addEventListener(evt, function (e) {
        e.preventDefault();
        dropzone.classList.add('drag-over');
      });
    });
    ['dragleave', 'dragend'].forEach(function (evt) {
      dropzone.addEventListener(evt, function () {
        dropzone.classList.remove('drag-over');
      });
    });
    dropzone.addEventListener('drop', function (e) {
      e.preventDefault();
      dropzone.classList.remove('drag-over');
      if (input && e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        showSelectedFile(input.files[0]);
      }
    });
  }

  // ---------- avatar picker (settings page — real upload, live preview) ----------
  var avatarBtn = document.getElementById('avatarPickerBtn');
  var avatarInput = document.getElementById('avatarInput');
  var avatarPreview = document.getElementById('avatarPreviewImg');
  var avatarFileName = document.getElementById('avatarFileName');
  if (avatarBtn && avatarInput && avatarPreview) {
    avatarBtn.addEventListener('click', function () {
      avatarInput.click();
    });
    avatarInput.addEventListener('change', function () {
      if (avatarInput.files && avatarInput.files[0]) {
        var file = avatarInput.files[0];
        var reader = new FileReader();
        reader.onload = function (e) {
          avatarPreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
        if (avatarFileName) {
          avatarFileName.textContent = file.name;
        }
      }
    });
  }

});
