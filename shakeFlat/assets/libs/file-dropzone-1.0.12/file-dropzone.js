/*
 * loopRequest v1.0.12
 * https://github.com/zhanziyang/file-dropzone
 * 
 * Copyright (c) 2020 zhanziyang
 * Released under the MIT license
 */
  
(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(require('jquery')) :
	typeof define === 'function' && define.amd ? define(['jquery'], factory) :
	(global.FileDropzone = factory(global.jQuery));
}(this, (function ($) { 'use strict';

$ = $ && 'default' in $ ? $['default'] : $;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
  return typeof obj;
} : function (obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};











var classCallCheck = function (instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
};

var createClass = function () {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  return function (Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();

var u = {
  isString: function isString(val) {
    return typeof val === 'string' || val instanceof String;
  },
  isArray: function isArray(val) {
    return Object.prototype.toString.call(val) === '[object Array]';
  },
  isObject: function isObject(val) {
    return val && (typeof val === 'undefined' ? 'undefined' : _typeof(val)) === 'object' && !this.isArray(val);
  },
  addUnique: function addUnique(arr, val) {
    if (arr.indexOf(val) < 0) {
      arr.push(val);
    }
    return arr;
  },
  remove: function remove(arr, val) {
    var index = arr.indexOf(val);
    if (index >= 0) {
      arr.splice(index, 1);
    }

    return arr;
  },
  assign: function assign(target, source) {
    if (!target || !source) {
      return target;
    }

    for (var key in source) {
      if (source.hasOwnProperty(key)) {
        target[key] = source[key];
      }
    }

    return target;
  },
  without: function without(list, rejectedItem) {
    var item, j, len, results;
    results = [];
    for (j = 0, len = list.length; j < len; j++) {
      item = list[j];
      if (item !== rejectedItem) {
        results.push(item);
      }
    }
    return results;
  },
  fileTypeValid: function fileTypeValid(file, type) {
    if (!type) {
      return true;
    }

    var baseMimetype = type.replace(/\/.*$/, '');
    var types = type.split(',');
    var _iteratorNormalCompletion = true;
    var _didIteratorError = false;
    var _iteratorError = undefined;

    try {
      for (var _iterator = types[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
        var _type = _step.value;

        var t = _type.trim();
        if (/^\./.test(t)) {
          var filename = file.name.toLowerCase();
          if (filename.split('.').pop() === t.toLowerCase()) return true;
        } else if (/\/\*$/.test(t)) {
          var fileBaseType = file.type.replace(/\/.*$/, '');
          if (fileBaseType === baseMimetype) {
            return true;
          }
        } else if (file.type === _type) {
          return true;
        }
      }
    } catch (err) {
      _didIteratorError = true;
      _iteratorError = err;
    } finally {
      try {
        if (!_iteratorNormalCompletion && _iterator.return) {
          _iterator.return();
        }
      } finally {
        if (_didIteratorError) {
          throw _iteratorError;
        }
      }
    }

    return false;
  },
  eventHasFile: function eventHasFile(evt) {
    var dt = evt.dataTransfer || evt.originalEvent.dataTransfer;
    if (dt.types) {
      for (var i = 0, len = dt.types.length; i < len; i++) {
        if (dt.types[i] == "Files") {
          return true;
        }
      }
    }

    return false;
  },
  getFilesFromEvent: function getFilesFromEvent(evt) {
    var dt = evt.dataTransfer || evt.originalEvent.dataTransfer;
    var files = [];
    if (dt.items && dt.items.length) {
      for (var i = 0, len = dt.items.length; i < len; i++) {
        var item = dt.items[i];
        if (item.kind === 'file') {
          files.push(item.getAsFile());
        }
      }
    } else {
      files = dt.files;
    }

    return files;
  }
};

var noop = function noop() {};
var _files = [];
var _setFiles = function _setFiles(files) {
  _files = files;
};
var dragTrack = 0;

var DEFAULTS = {
  target: '',
  fileHoverClass: 'dropzone--file-hover',
  clickable: true,
  multiple: true,
  paramName: 'file',
  accept: '',
  capture: false,
  unique: false,
  noFolder: true,
  forceReplace: false,
  onChange: noop,
  onEnter: noop,
  onLeave: noop,
  onHover: noop,
  onDrop: noop,
  onFolderFound: noop,
  onInvalid: noop,
  beforeAdd: noop
};

var FileDropzone = function () {
  function FileDropzone(selector, options) {
    classCallCheck(this, FileDropzone);

    var opts = options || {};
    var target = $(selector);

    if (!u.isString(selector) && u.isObject(selector) && !(selector instanceof $) && !(selector instanceof Element)) {
      opts = selector || {};
      target = $(opts.target);
    }

    if (!target || target.length <= 0) {
      throw new Error('No matched element.');
    }

    this.element = target;
    this.options = Object.assign(JSON.parse(JSON.stringify(DEFAULTS)), opts);
    this.disabled = false;
    this.fileInput = null;
    this.multiple = false;

    this.init();
  }

  createClass(FileDropzone, [{
    key: 'init',
    value: function init() {
      _setFiles.bind(this)([]);
      this.clickable = this.options.clickable;
      if (this.clickable) {
        this.enableClick();
      }
      this.element[0].addEventListener('click', _handleClick.bind(this));
      this.multiple = typeof this.options.multiple === 'boolean' ? this.options.multiple : true;

      this.fileInput = $('<input type="file" hidden name="' + this.options.paramName + '" class="' + this.options.paramName + '" >');

      this.element.next(this.fileInput);

      if (this.multiple) {
        this.fileInput.attr('multiple', 'multiple');
      }

      if (this.options.capture) {
        this.fileInput.attr('capture', this.options.capture);
      }

      if (this.options.accept) {
        this.fileInput.attr('accept', this.options.accept);
      }

      this.element.on('dragenter', _handleDragEnter.bind(this)).on('dragleave', _handleDragLeave.bind(this)).on('dragend', _handleDragLeave.bind(this)).on('dragover', _handleDragOver.bind(this)).on('drop', _handleDrop.bind(this));

      var that = this;

      this.fileInput.on('click', function (evt) {
        evt.stopPropagation();
      }).on('change', function (evt) {
        if (that.disabled) return;
        var fileList = evt.target.files;
        var files = [];
        for (var i = 0, len = fileList.length; i < len; i++) {
          var value = fileList[i];
          if (value instanceof File) {
            files.push(value);
          }
        }
        _addFiles.bind(that)(files);
        if (!that.options.unique) {
          $(this).val('');
        }
      });

      _insetStyles();
    }
  }, {
    key: 'addFiles',
    value: function addFiles(files) {
      _addFiles.bind(this)(files);
    }
  }, {
    key: 'getFiles',
    value: function getFiles() {
      return _files;
    }
  }, {
    key: 'removeFile',
    value: function removeFile(arg) {
      var files = this.getFiles();
      var oldLen = files.length;
      var fileToRemove;
      if (arg instanceof File) {
        fileToRemove = arg;
        var newList = u.without(files, fileToRemove);
        _setFiles.bind(this)(newList);
      } else if (typeof arg === 'number') {
        fileToRemove = files.splice(arg, 1)[0];
      }
      if (this.getFiles().length === oldLen - 1) {
        this.options.onChange && this.options.onChange.bind(this)();
        return fileToRemove;
      } else {
        return null;
      }
    }
  }, {
    key: 'pop',
    value: function pop() {
      var files = this.getFiles();
      if (!files.length) {
        return null;
      }
      var removed = files.pop();
      _setFiles.bind(this)(files);
      this.options.onChange && this.options.onChange.bind(this)();
      return removed;
    }
  }, {
    key: 'shift',
    value: function shift() {
      var files = this.getFiles();
      if (!files.length) {
        return null;
      }
      var removed = files.shift();
      _setFiles.bind(this)(files);
      this.options.onChange && this.options.onChange.bind(this)();
      return removed;
    }
  }, {
    key: 'openFileChooser',
    value: function openFileChooser() {
      this.fileInput.click();
    }
  }, {
    key: 'clearAll',
    value: function clearAll() {
      _setFiles.bind(this)([]);
      this.options.onChange && this.options.onChange.bind(this)();
    }
  }, {
    key: 'disable',
    value: function disable() {
      this.disabled = true;
      this.element.addClass('dropzone--disabled');
      this.fileInput.prop('disabled', true);
    }
  }, {
    key: 'enable',
    value: function enable() {
      this.disabled = false;
      this.element.removeClass('dropzone--disabled');
      this.fileInput.prop('disabled', false);
    }
  }, {
    key: 'disableClick',
    value: function disableClick() {
      this.element.removeClass('dropzone--clickable');
      this.clickable = false;
    }
  }, {
    key: 'enableClick',
    value: function enableClick() {
      this.element.addClass('dropzone--clickable');
      this.clickable = true;
    }
  }], [{
    key: 'getFileSize',
    value: function getFileSize(file) {
      var unit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'b';

      var units = ['b', 'kb', 'mb', 'gb', 'tb'];
      if (!(file instanceof File)) {
        throw new TypeError('First argument "file" should be a File instance.');
      }
      if (!unit || typeof unit !== 'string') {
        unit = 'b';
      }
      if (unit == 'b') return file.size;

      var unitIndex = units.indexOf(unit.toLowerCase());
      if (unitIndex < 0) {
        throw new TypeError('The unit should be one of "tb", "gb", "mb", "kb" and "b", the default value is "b"');
      }

      return file.size / Math.pow(1024, unitIndex);
    }
  }]);
  return FileDropzone;
}();

function _addFiles(files) {
  var _this = this;

  var valid = [];
  var invalid = [];
  files.forEach(function (file) {
    if (u.fileTypeValid(file, _this.options.accept)) {
      if (!_this.options.unique || _this.getFiles().indexOf(file) < 0) {
        valid.push(file);
      }
    } else {
      invalid.push(file);
    }
  });

  if (invalid.length) {
    this.options.onInvalid && this.options.onInvalid.bind(this)(invalid);
  }

  if (!valid[0]) return;

  if (!this.multiple) {
    valid = valid.slice(0, 1);
  }

  var canAdd = true;
  if (this.options.beforeAdd) {
    var result = this.options.beforeAdd.bind(this)(valid);
    if (typeof result == 'boolean') {
      canAdd = result;
    }
  }

  if (!canAdd) return;

  if (!this.multiple || this.options.forceReplace) {
    _setFiles.bind(this)(valid);
  } else {
    _setFiles.bind(this)(this.getFiles().concat(valid));
  }
  this.options.onChange && this.options.onChange.bind(this)();
}

function _insetStyles() {
  if (!window.__dropzone_styled_inserted) {
    $('<style>\n      .dropzone--clickable { cursor: pointer; }\n      .dropzone--file-hover { box-shadow: inset 0 0 10px #aaa; }\n    </style>').appendTo("head");

    window.__dropzone_styled_inserted = true;
  }
}

function _handleDragEnter(evt) {
  dragTrack++;
  if (this.disabled || !u.eventHasFile(evt)) return;
  evt.preventDefault();
  evt.stopPropagation();
  if (dragTrack == 1) {
    this.options.onEnter && this.options.onEnter.bind(this)(evt);
    this.element.addClass(this.options.fileHoverClass);
  }
}

function _handleDragLeave(evt) {
  dragTrack--;
  if (this.disabled || !u.eventHasFile(evt)) return;
  evt.preventDefault();
  evt.stopPropagation();
  if (dragTrack === 0) {
    this.options.onLeave && this.options.onLeave.bind(this)(evt);
    this.element.removeClass(this.options.fileHoverClass);
  }
}

function _handleDragOver(evt) {
  if (this.disabled || !u.eventHasFile(evt)) return;
  evt.preventDefault();
  // evt.stopPropagation()
  this.options.onHover && this.options.onHover.bind(this)(evt);
}

function _handleDrop(evt) {
  dragTrack--;
  if (this.disabled || !u.eventHasFile(evt)) return;
  evt.preventDefault();
  evt.stopPropagation();
  this.options.onDrop && this.options.onDrop.bind(this)(evt);
  this.element.removeClass(this.options.fileHoverClass);
  var files = _detectFolders.bind(this)(u.getFilesFromEvent(evt));

  _addFiles.bind(this)(files);
}

function _handleClick(evt) {
  if (this.disabled) return;
  if (!this.clickable) return;
  this.openFileChooser();
}

function _detectFolders(files) {
  var folders = [];
  var realFiles = [];
  for (var i = 0, len = files.length; i < len; i++) {
    var f = files[i];
    if (!f.type && f.size % 4096 == 0) {
      folders.push(f);
    } else {
      realFiles.push(f);
    }
  }

  if (folders.length > 0) {
    this.options.onFolderFound && this.options.onFolderFound.bind(this)(folders);
  }
  if (this.options.noFolder) {
    return realFiles;
  } else {
    return files;
  }
}

return FileDropzone;

})));
