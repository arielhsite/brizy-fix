jQuery(document).ready(function($) {
	var posts = [];
	var currentIndex = 0;
	var compiledCount = 0;
	var failedCount = 0;

	$('#brizy-fix-start-btn').on('click', function(e) {
		e.preventDefault();
		
		// Disable button.
		$(this).prop('disabled', true).text(brizyFixData.messages.processing);
		
		// Show progress section and clear log.
		$('#brizy-fix-progress-section').show();
		$('#brizy-fix-log').empty();
		$('#brizy-fix-review-invitation').hide();
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

	function compileNext() {
		if (currentIndex >= posts.length) {
			// Done!
			$('#brizy-fix-progress-title').text(brizyFixData.messages.complete);
			logMessage(brizyFixData.messages.finished, 'success');
			$('#brizy-fix-review-invitation').show();
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
});
