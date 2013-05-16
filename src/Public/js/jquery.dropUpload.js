/*
	jQuery plugin for drag/drop uploads in HTML5

	Author:
		Pomle
	Email:
		pontus.alexander@gmail.com

	Licensed under:
		Kopimi, no rights reserved

	Project home:
		https://github.com/pomle/jquery-dropUpload

	Version:
		0.5.5

	Usage:
		See Example.html
*/

(function( $ ){

	$.event.props.push("dataTransfer");

	var emptyCallback = function() {};

	var defaultSettings = {
		'fileDropCountMax': null,
		'fileMeta': emptyCallback,
		'fileParamName': 'dropUploadFile',
		'fileSimTransfers': 1,
		'fileSizeMax': null,

		'onComplete': emptyCallback,
		'onDropError': emptyCallback,
		'onDropSuccess': emptyCallback,
		'onDragEnter': emptyCallback,
		'onDragOver': emptyCallback,
		'onDragLeave': emptyCallback,

		'onFileCompleted': emptyCallback,
		'onFileFailed': function(File, message)
		{
			alert(message);
		},
		'onFileQueued': emptyCallback,
		'onFileSucceeded': emptyCallback,
		'onFileStarted': emptyCallback,

		'onProgressUpdated': emptyCallback,

		'onQueueCompleted': emptyCallback,

		'url': ''
		};

	$.fn.dropUpload = function(method)
	{
		var
			isLoopRunning = false,
			loopSize = 0,
			queue = [],
			settings = {};

		var eventDrop = function(e)
		{
			e.preventDefault();

			try
			{
				if( !e.dataTransfer.files || e.dataTransfer.files.length == 0 )
					throw('FILE_ARRAY_EMPTY');

				var FileList = e.dataTransfer.files;

				if( settings.fileDropCountMax && FileList.length > settings.fileDropCountMax )
					throw('FILE_DROP_COUNT_MAX');

				settings.onDropSuccess();
			}
			catch(e)
			{
				settings.onDropError(e.message);
				return false;
			}

			filesHandler(FileList);

			return true;
		}

		var eventDragEnter = function(e)
		{
			settings.onDragEnter();
		}

		var eventDragLeave = function(e)
		{
			settings.onDragLeave();
		}

		var eventDragOver = function(e)
		{
			e.preventDefault();
			settings.onDragOver();
		}

		// A method to disable browser default behavior for certian events
		var eventKillDefault = function(e)
		{
			e.preventDefault();
			return false;
		}

		var filesHandler = function(FileList)
		{
			// Iterate over all files and add to queue if isFileAccepted() returns true
			filesIterator(FileList, function(File) {
				if(isFileAccepted(File))
					queueFile(File);
			});

			/*
				Engage upload loop if not already running
				Notice that it is allowed to start several instances, but it's recommended to control the simultaneous queue length with fileSimTransfers setting
			*/
			if( !isLoopRunning )
				uploadLoopEngage();
		}

		// Lets us iterate over file lists in a consistent manner
		var filesIterator = function(FileList, callback)
		{
			for(var index = 0; index < FileList.length; index++)
				callback(FileList[index]);

			return true;
		}

		// Returns wheater file is an acceptable upload or not
		var isFileAccepted = function(File)
		{
			if( settings.fileSizeMax && (File.size > settings.fileSizeMax) )
				return false;

			return true;
		}

		var queueFile = function(File)
		{
			File.meta = settings.fileMeta() || {}; // If user function returns any data, put it on the File object

			queue.push(File);

			settings.onFileQueued(File);
		}

		// This function not totally quirk free as of now
		var uploadLoopEngage = function()
		{
			isLoopRunning = true;

			while( queue.length > 0 && loopSize < settings.fileSimTransfers )
			{
				var File = queue.shift();

				try
				{
					loopSize++;
					// uploadLoopEngage is sent as a callback for when the upload completes
					uploadFile(File, uploadLoopEngage);
				}
				catch(e)
				{
					loopSize--;
					// Inform plugin about failure
					settings.onFileFailed(File, e.message);
					settings.onFileCompleted(File);
				}
			}

			isLoopRunning = false;
		}

		var uploadFile = function(File, onCompleteCallback)
		{
			//loopSize++;

			settings.onFileStarted(File);

			var File = File;
			var FR = new FileReader();

			// Defines the call that is made when upload has completed
			var uploadFinished = function()
			{
				loopSize--;

				settings.onProgressUpdated(File, 1);
				settings.onFileCompleted(File);

				if( typeof onCompleteCallback == 'function' )
					onCompleteCallback();

				if( loopSize == 0 )
					settings.onQueueCompleted();
			}

			FR.File = File;
			FR.onload = function(e) // Prepares file and meta data for the POST-stream
			{
				var payload = new FormData();

				// Adds Meta data (connected by user defined function fileMeta()
				$.each(this.File.meta, function(name, value)
				{
					payload.append(name, value);
				});

				payload.append(settings.fileParamName, File);

				var XHR = new XMLHttpRequest();
				XHR.open("POST", settings.url, true); // Perform asynchronous transfer

				XHR.onerror = function(e)
				{
					settings.onFileFailed(File);
					uploadFinished();
				};

				XHR.onload = function(e) // Triggers on completed upload
				{
					settings.onFileSucceeded(File, this.responseText); // reponseText is the response body printed by the server
					uploadFinished();
				};

				XHR.upload.onprogress = function(e)
				{
					if (e.lengthComputable)
						settings.onProgressUpdated(File, e.loaded / e.total);
				};

				XHR.send(payload);
			}

			// Initiates reading and puts us in FR.onload on complete
			FR.readAsBinaryString(File);
		};

		var methods = {
			init: function( userOptions ) {

				settings = $.extend({}, defaultSettings, userOptions);

				// I think this is to prevent the browser from opening the file
				$(window)
					.off('.dropUpload')
					.on('drop.dropUpload', eventKillDefault)
					.on('dragenter.dropUpload', eventKillDefault)
					.on('dragover.dropUpload', eventKillDefault)
					.on('dragleave.dropUpload', eventKillDefault)
					;

				return this.each(function(){

					var fileSelect = $('<input type="file" multiple accept="*">');

					fileSelect.on("change", function(e) {
						filesHandler(this.files);
					});

					$(this)
						.on('click.dropUpload', function(e) { fileSelect.click(); })

						.on('drop.dropUpload', eventDrop)
						.on('dragover.dropUpload', eventDragOver)

						// dragenter and dragleave are inherently buggy and will cause problems with text
						.on('dragenter.dropUpload', eventDragEnter)
						.on('dragleave.dropUpload', eventDragLeave)
						;
				});
			},
			destroy: function()
			{
				$(window).off('.dropUpload');

				return this.each(function(){
					$(this).off('.dropUpload');

				});
			}
		};

		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.dropUpload' );
		}
	};

	return this;

})( jQuery );