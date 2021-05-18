<div id="bp-nouveau-activity-form" class="activity-update-form">
	<form name="whats-new-form" method="post" id="whats-new-form" class="activity-form focus-in">
		<div id="whats-new-avatar">
			<a class="activity-post-avatar" href="#">
				<img src="<?php echo esc_url( bpb_get_dummy_avatar_url( 'member', 50 ) ); ?>" class="avatar user-1-avatar avatar-50 photo" width="50" height="50" alt="Profile photo of admin">
				<span class="user-name">admin</span>
			</a>
		</div>
		<div id="whats-new-content">
			<div id="whats-new-textarea">
				<div name="whats-new" cols="50" rows="4" placeholder="Write here or use @ to mention someone." aria-label="Post what's new" contenteditable="true" data-suggestions-group-id="false" id="whats-new" class="bp-suggestions medium-editor-element" style="resize: vertical; height: auto;" spellcheck="true" data-medium-editor-element="true" role="textbox" aria-multiline="true" data-medium-editor-editor-index="1" medium-editor-index="18369f97-f149-8317-3225-9f2a90da08ca" data-placeholder="" data-medium-focused="true"></div>
			</div>
			<div id="medium-editor-toolbar-1" class="medium-editor-toolbar static-toolbar">
				<ul id="medium-editor-toolbar-actions1" class="medium-editor-toolbar-actions" style="display: block;">
					<li><button class="medium-editor-action medium-editor-action-bold medium-editor-button-first" data-action="bold" title="bold" aria-label="bold"><b>B</b></button></li>
					<li><button class="medium-editor-action medium-editor-action-italic" data-action="italic" title="italic" aria-label="italic"><b><i>I</i></b></button></li>
					<li><button class="medium-editor-action medium-editor-action-unorderedlist" data-action="insertunorderedlist" title="unordered list" aria-label="unordered list"><b>•</b></button></li>
					<li><button class="medium-editor-action medium-editor-action-orderedlist" data-action="insertorderedlist" title="ordered list" aria-label="ordered list"><b>1.</b></button></li>
					<li><button class="medium-editor-action medium-editor-action-quote" data-action="append-blockquote" title="blockquote" aria-label="blockquote"><b>“</b></button></li>
					<li><button class="medium-editor-action medium-editor-action-anchor" data-action="createLink" title="link" aria-label="link"><b>#</b></button></li>
					<li><button class="medium-editor-action medium-editor-action-pre medium-editor-button-last" data-action="append-pre" title="preformatted text" aria-label="preformatted text"><b>0101</b></button></li>
				</ul>
				<div class="medium-editor-toolbar-form" id="medium-editor-toolbar-form-anchor-1">
					<input type="text" class="medium-editor-toolbar-input" placeholder="Paste or type a link">
					<a href="#" class="medium-editor-toolbar-save">✓</a><a href="#" class="medium-editor-toolbar-close">×</a>
				</div>
			</div>
		</div>
		<div id="whats-new-attachments" class="empty">
			<div class="activity-media-container">
				<div class="dropzone closed" id="activity-post-media-uploader"></div>
			</div>
			<div class="activity-document-container">
				<div class="dropzone closed" id="activity-post-document-uploader"></div>
			</div>
			<div class="activity-attached-gif-container" style="height: 0px; width: 0px;">	</div>
		</div>

		<div id="whats-new-toolbar">
			<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="up-left" data-bp-tooltip="Show formatting" data-bp-tooltip-hide="Hide formatting" data-bp-tooltip-show="Show formatting">
				<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
					<span class="bb-icon bb-icon-text-format"></span>
				</a>
			</div>
			<div class="post-elements-buttons-item post-media media-support">
				<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="Attach a photo">
					<i class="bb-icon bb-icon-camera-small"></i>
				</a>
			</div>
		</div>
		<div id="activity-form-submit-wrapper">
			<div id="whats-new-post-in-box" class="in-profile">
				<select name="whats-new-post-in" aria-label="Post in" id="whats-new-post-in">
				<option value="profile">Post in: Profile</option>
				<option value="group">Post in: Group</option>
				</select>
			</div>
			<div id="activity-post-form-privacy">
				<select id="bp-activity-privacy" class="bp-activity-privacy" name="privacy">
					<option value="public">Public</option>
					<option value="loggedin">All Members</option>
					<option value="friends">My Connections</option>
					<option value="onlyme">Only Me</option>
				</select>
			</div>
			<div id="whats-new-submit" class="in-profile">
				<input type="submit" id="aw-whats-new-submit" class="button" name="aw-whats-new-submit" value="Post Update">
				<input type="reset" id="aw-whats-new-reset" class="text-button small" value="Cancel">
			</div>
		</div>
	</form>
</div>
