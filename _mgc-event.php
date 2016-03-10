<?php namespace ProcessWire;

/**
 * Template file for use with the MarkupGoogleCalendar module
 * 
 * You should copy/move this file to /site/templates/_mgc-event.php and modify
 * as you see fit. 
 * 
 * Variables provided to this file:
 * ================================
 * @var \Google_Service_Calendar_Event $event
 * @var string $startDate Starting date string
 * @var string $startTime Starting time string
 * @var string $startDateTime Starting date and time string together
 * @var int $startTS Start date/time as unix timestamp
 * @var string $endDate Ending date string
 * @var string $endTime Ending time string
 * @var string $endDateTime Ending date and time string together
 * @var int $endTS End date/time as unix timestamp
 * @var string $dateRange Starting and ending date in a string
 * @var string $summary Event summary or title
 * @var string $location Event location (when applicable)
 * @var string $description Event description (when applicable)
 * @var string $htmlLink HTTP link to more information or Google Calendar detail page
 * 
 * PLEASE NOTE
 * ===========
 * The markup below is coded according to schema.org event type using RDFa, and uses
 * class names prefixed with "mgc-". This is for example purposes only and none of
 * this is required, so feel free to change this or redo entirely however you'd like.
 * 
 * Also: $summary, $location, $description and $htmlLink are entity encoded. 
 * 
 */

?>

<div vocab="http://schema.org/" typeof="Event" class="mgc-event">
	<h3 property="name" class="mgc-summary"><?=$summary?></h3>
	<p class="mgc-dates">
		<meta property="startDate" content="<?=date('c', $startTS)?>">
			<span class='mgc-start-date'><?=$startDate?></span>
			<? if($startTime): ?>
				<span class='mgc-start-time'><?=$startTime?></span>
			<? endif; ?>
		</meta>
		<? if($endDate || $endTime): ?> – 
			<meta property="endDate" content="<?=date('c', $endTS)?>">
				<? if($endDate): ?>
					<span class='mgc-end-date'><?=$endDate?></span>
				<? endif; ?>	
				<? if($endTime): ?>
					<span class='mgc-end-time'><?=$endTime?></span>
				<? endif; ?>
			</meta>
		<? endif; ?>
	</p>
	<? if($location): ?> 
		<p class="mgc-location" property="location" typeof="Place">
			<?=$location?>
		</p>
	<? endif; ?>
	<? if($description): ?>
		<p class="mgc-description" property="description">	
			<?=nl2br($description)?>
		</p>	
	<? endif; ?>
</div>
