jQuery(document).ready(function($) {
	var posts = [];
	var currentIndex = 0;
	var compiledCount = 0;
	var failedCount = 0;
	var mediaPosts = [];
	var mediaIndex = 0;
	var mediaCheckedCount = 0;
	var missingMedia = [];
	var missingMediaKeys = {};
	var affectedPageIds = {};

	$('#brizy-fix-start-btn').on('click', function(e) {
		e.preventDefault();
		
		// Disable button.
		$(this).prop('disabled', true).text(brizyFixData.messages.processing);
		
		// Show progress section and clear log.
		$('#brizy-fix-progress-section').show();
		$('#brizy-fix-log').empty();
		$('#brizy-fix-progress-title').text(brizyFixData.messages.fetching);
		
		// Step 1: Get posts.
		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_get_posts',
				security: brizyFixData.nonce
			},
			success: function(response) {
				if (response.success && response.data && response.data.length > 0) {
					posts = response.data;
					currentIndex = 0;
					compiledCount = 0;
					failedCount = 0;
					updateProgress();
					compileNext();
				} else {
					logMessage(brizyFixData.messages.noPages, 'error');
					resetBtn();
				}
			},
			error: function() {
				logMessage(brizyFixData.messages.failedList, 'error');
				resetBtn();
			}
		});
	});

	$('#brizy-fix-media-scan-btn').on('click', function(e) {
		e.preventDefault();

		$(this).prop('disabled', true).text(brizyFixData.messages.processing);
		$('#brizy-fix-media-placeholder-btn').prop('disabled', true);
		$('#brizy-fix-media-remove-placeholder-btn').prop('disabled', true);
		$('#brizy-fix-media-progress-section').show();
		$('#brizy-fix-media-results').hide();
		$('#brizy-fix-media-results-body').empty();
		$('#brizy-fix-media-log').empty();
		$('#brizy-fix-media-progress-title').text(brizyFixData.messages.mediaScanStart);

		mediaPosts = [];
		mediaIndex = 0;
		mediaCheckedCount = 0;
		missingMedia = [];
		missingMediaKeys = {};
		affectedPageIds = {};
		updateMediaProgress();

		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_get_media_scan_posts',
				security: brizyFixData.nonce
			},
			success: function(response) {
				if (response.success && response.data && response.data.length > 0) {
					mediaPosts = response.data;
					updateMediaProgress();
					scanNextMediaPost();
				} else {
					logMediaMessage(brizyFixData.messages.noPages, 'error');
					resetMediaScanButton();
				}
			},
			error: function() {
				logMediaMessage(brizyFixData.messages.failedList, 'error');
				resetMediaScanButton();
			}
		});
	});

	$('#brizy-fix-media-placeholder-btn').on('click', function(e) {
		e.preventDefault();

		if (!getMissingWithoutPlaceholders().length) {
			logMediaMessage(brizyFixData.messages.mediaNoReport, 'warning');
			return;
		}

		$(this).prop('disabled', true).text(brizyFixData.messages.processing);
		logMediaMessage(brizyFixData.messages.mediaPlaceholderStart, 'warning');
		createNextPlaceholderBatch(getMissingWithoutPlaceholders(), 0);
	});

	$('#brizy-fix-media-remove-placeholder-btn').on('click', function(e) {
		e.preventDefault();

		if (!getPlaceholderMedia().length) {
			logMediaMessage(brizyFixData.messages.mediaNoReport, 'warning');
			return;
		}

		$(this).prop('disabled', true).text(brizyFixData.messages.processing);
		logMediaMessage(brizyFixData.messages.mediaRemovePlaceholderStart, 'warning');
		removeNextPlaceholderBatch(getPlaceholderMedia(), 0);
	});

	function compileNext() {
		if (currentIndex >= posts.length) {
			// Done!
			$('#brizy-fix-progress-title').text(brizyFixData.messages.complete);
			logMessage(brizyFixData.messages.finished, 'success');
			resetBtn();
			return;
		}

		var post = posts[currentIndex];
		$('#brizy-fix-progress-title').text(brizyFixData.messages.compiling + '"' + post.title + '" (ID: ' + post.id + ')...');

		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_compile_post',
				post_id: post.id,
				security: brizyFixData.nonce
			},
			success: function(response) {
				if (response.success && response.data && response.data.success) {
					compiledCount++;
					logMessage(brizyFixData.messages.compiling + '"' + post.title + '" (ID: ' + post.id + ')' + brizyFixData.messages.compiled, 'success');
				} else {
					failedCount++;
					var errMsg = (response.data && response.data.error) ? response.data.error : 'unknown error';
					logMessage(brizyFixData.messages.compiling + '"' + post.title + '" (ID: ' + post.id + ')' + brizyFixData.messages.failed + errMsg, 'error');
				}
				currentIndex++;
				updateProgress();
				compileNext();
			},
			error: function() {
				failedCount++;
				logMessage(brizyFixData.messages.compiling + '"' + post.title + '" (ID: ' + post.id + ')' + brizyFixData.messages.reqFailed, 'error');
				currentIndex++;
				updateProgress();
				compileNext();
			}
		});
	}

	function updateProgress() {
		var percent = (currentIndex / posts.length) * 100;
		$('#brizy-fix-progress-bar').css('width', percent + '%');
		$('#brizy-fix-progress-text').text(currentIndex + ' / ' + posts.length + ' (' + compiledCount + ' ' + brizyFixData.messages.successful + ', ' + failedCount + ' ' + brizyFixData.messages.failedSkipped + ')');
	}

	function logMessage(msg, type) {
		var item = $('<div class="brizy-fix-log-item"></div>').text(msg).addClass(type);
		var log = $('#brizy-fix-log');
		log.append(item);
		log.scrollTop(log[0].scrollHeight);
	}

	function resetBtn() {
		$('#brizy-fix-start-btn').prop('disabled', false).text(brizyFixData.messages.start);
	}

	function scanNextMediaPost() {
		if (mediaIndex >= mediaPosts.length) {
			$('#brizy-fix-media-progress-title').text(brizyFixData.messages.mediaScanDone);
			updateMediaProgress();
			renderMediaResults();
			resetMediaScanButton();
			return;
		}

		var post = mediaPosts[mediaIndex];
		$('#brizy-fix-media-progress-title').text(brizyFixData.messages.mediaScanning + '"' + post.title + '"...');

		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_scan_media_post',
				post_id: post.id,
				security: brizyFixData.nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					mediaCheckedCount += parseInt(response.data.checked_count, 10) || 0;
					addMissingMediaItems(response.data.missing || [], response.data.post_id, response.data.post_title);
				} else {
					logMediaMessage('Could not scan "' + post.title + '". The scan will continue with the next item.', 'error');
				}

				mediaIndex++;
				updateMediaProgress();
				scanNextMediaPost();
			},
			error: function() {
				logMediaMessage('Could not scan "' + post.title + '". The scan will continue with the next item.', 'error');
				mediaIndex++;
				updateMediaProgress();
				scanNextMediaPost();
			}
		});
	}

	function addMissingMediaItems(items, postId, postTitle) {
		if (!items.length) {
			return;
		}

		affectedPageIds[postId] = true;
		logMediaMessage(brizyFixData.messages.mediaFound + '"' + postTitle + '".', 'warning');

		$.each(items, function(index, item) {
			var key = item.uid || (item.attachment_id + ':' + item.local_path);
			if (!missingMediaKeys[key]) {
				item.affected_pages = [];
				missingMediaKeys[key] = item;
				missingMedia.push(item);
			}

			missingMediaKeys[key].affected_pages.push({
				id: postId,
				title: postTitle
			});
		});
	}

	function updateMediaProgress() {
		var total = mediaPosts.length || 0;
		var percent = total ? (mediaIndex / total) * 100 : 0;
		$('#brizy-fix-media-progress-bar').css('width', percent + '%');
		$('#brizy-fix-media-progress-text').text(mediaIndex + ' / ' + total + ' (' + missingMedia.length + ' missing file(s), ' + mediaCheckedCount + ' media reference(s) checked)');
	}

	function renderMediaResults() {
		var affectedCount = Object.keys(affectedPageIds).length;

		if (!missingMedia.length) {
			$('#brizy-fix-media-results').show();
			$('#brizy-fix-media-summary').text(brizyFixData.messages.mediaScanNone);
			$('#brizy-fix-media-placeholder-btn').prop('disabled', true);
			$('#brizy-fix-media-remove-placeholder-btn').prop('disabled', true);
			logMediaMessage(brizyFixData.messages.mediaScanNone, 'success');
			return;
		}

		$('#brizy-fix-media-summary').text('Found ' + missingMedia.length + ' missing media file(s) across ' + affectedCount + ' affected page(s). Download the original files from the source links and place them into the exact local paths shown below. You may create yellow placeholders now so the front end stops showing broken image requests while you gather the originals, or remove existing yellow placeholders if you no longer want them.');
		$('#brizy-fix-media-results-body').empty();

		$.each(missingMedia, function(index, item) {
			var mediaName = $('<div></div>').text(item.title || 'Untitled media');
			var mediaMeta = $('<small></small>').text('Attachment ID: ' + (item.attachment_id || 'not found') + ' | Brizy media ID: ' + (item.uid || 'not found'));
			var mediaCell = $('<td></td>').append(mediaName).append(mediaMeta);
			var pathCell = $('<td class="brizy-fix-path"></td>').text(item.local_path || 'No local path found.');
			var statusText = item.is_placeholder ? 'A yellow placeholder currently exists for this file.' : 'The expected local file is missing.';
			var sourceCell = $('<td></td>');
			var pagesCell = $('<td></td>');

			if (item.source_url) {
				sourceCell.append($('<a></a>', {
					href: item.source_url,
					target: '_blank',
					rel: 'noopener noreferrer',
					text: 'Open original image'
				}));
			} else {
				sourceCell.text('No original source link was found in the database.');
			}

			pathCell.append($('<small></small>').text(statusText));
			pagesCell.text(uniquePageTitles(item.affected_pages).join(', '));

			$('#brizy-fix-media-results-body').append(
				$('<tr></tr>').append(mediaCell, pathCell, sourceCell, pagesCell)
			);
		});

		$('#brizy-fix-media-results').show();
		$('#brizy-fix-media-placeholder-btn').prop('disabled', !getMissingWithoutPlaceholders().length);
		$('#brizy-fix-media-remove-placeholder-btn').prop('disabled', !getPlaceholderMedia().length);
	}

	function createNextPlaceholderBatch(items, startIndex) {
		var batch = items.slice(startIndex, startIndex + 5);
		var uids = $.map(batch, function(item) {
			return item.uid || null;
		});

		if (!batch.length) {
			logMediaMessage(brizyFixData.messages.mediaPlaceholderDone, 'success');
			$('#brizy-fix-media-placeholder-btn').prop('disabled', false).text(brizyFixData.messages.mediaPlaceholderButton);
			renderMediaResults();
			return;
		}

		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_create_media_placeholders',
				security: brizyFixData.nonce,
				uids: uids
			},
			success: function(response) {
				if (response.success && response.data && response.data.results) {
					$.each(response.data.results, function(index, result) {
						var type = result.status === 'created' ? 'success' : 'warning';
						if (result.status === 'created') {
							markPlaceholderState(result.uid, true);
						}
						logMediaMessage((result.title || result.uid) + ': ' + result.message, type);
					});
				} else {
					logMediaMessage('A placeholder batch could not be completed. The remaining items were skipped.', 'error');
				}

				createNextPlaceholderBatch(items, startIndex + 5);
			},
			error: function() {
				logMediaMessage('A placeholder batch could not be completed. The remaining items were skipped.', 'error');
				createNextPlaceholderBatch(items, startIndex + 5);
			}
		});
	}

	function removeNextPlaceholderBatch(items, startIndex) {
		var batch = items.slice(startIndex, startIndex + 5);
		var uids = $.map(batch, function(item) {
			return item.uid || null;
		});

		if (!batch.length) {
			logMediaMessage(brizyFixData.messages.mediaRemovePlaceholderDone, 'success');
			$('#brizy-fix-media-remove-placeholder-btn').prop('disabled', false).text(brizyFixData.messages.mediaRemovePlaceholderButton);
			renderMediaResults();
			return;
		}

		$.ajax({
			url: brizyFixData.ajaxurl,
			type: 'POST',
			data: {
				action: 'brizy_fix_remove_media_placeholders',
				security: brizyFixData.nonce,
				uids: uids
			},
			success: function(response) {
				if (response.success && response.data && response.data.results) {
					$.each(response.data.results, function(index, result) {
						var type = result.status === 'removed' ? 'success' : 'warning';
						if (result.status === 'removed') {
							markPlaceholderState(result.uid, false);
						}
						logMediaMessage((result.title || result.uid) + ': ' + result.message, type);
					});
				} else {
					logMediaMessage('A placeholder removal batch could not be completed. The remaining items were skipped.', 'error');
				}

				removeNextPlaceholderBatch(items, startIndex + 5);
			},
			error: function() {
				logMediaMessage('A placeholder removal batch could not be completed. The remaining items were skipped.', 'error');
				removeNextPlaceholderBatch(items, startIndex + 5);
			}
		});
	}

	function getMissingWithoutPlaceholders() {
		return $.grep(missingMedia, function(item) {
			return !item.is_placeholder;
		});
	}

	function getPlaceholderMedia() {
		return $.grep(missingMedia, function(item) {
			return !!item.is_placeholder;
		});
	}

	function markPlaceholderState(uid, isPlaceholder) {
		$.each(missingMedia, function(index, item) {
			if (item.uid === uid) {
				item.is_placeholder = isPlaceholder;
			}
		});
	}

	function uniquePageTitles(pages) {
		var seen = {};
		var titles = [];

		$.each(pages || [], function(index, page) {
			var key = page.id || page.title;
			if (!seen[key]) {
				seen[key] = true;
				titles.push(page.title || ('Post ID ' + page.id));
			}
		});

		return titles;
	}

	function logMediaMessage(msg, type) {
		var item = $('<div class="brizy-fix-log-item"></div>').text(msg).addClass(type);
		var log = $('#brizy-fix-media-log');
		log.append(item);
		log.scrollTop(log[0].scrollHeight);
	}

	function resetMediaScanButton() {
		$('#brizy-fix-media-scan-btn').prop('disabled', false).text(brizyFixData.messages.mediaScanButton);
	}
});
