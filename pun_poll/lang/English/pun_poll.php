<?php

/**
 * Lang file for voting
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_poll
 */

if (!defined('FORUM')) exit;

$lang_pun_poll = array(

	'Poll question'					=> 'Poll question',
	'Voting answer'					=> 'Poll option',
	'Able revote'					=> 'Allow users to change their opinions.',

	'Name plugin'					=> 'Settings for polls',
	'Maximum answers'				=> 'Maximum answers in poll (2-100).',
	'Disable revoting'				=> 'Allow vote change.',
	'Disable see results'			=> 'Enable read results',
	'Maximum answers info'			=> 'Maximum answers',
	'Disable see results info'		=> 'Users can see poll results without voting.',
	'Disable revoting info'			=> 'Enable revoting',
	'Poll add'						=> 'Allow users to use polls in their topics.',
	'Permission'					=> 'Voting permission',

	'Revote'						=> 'Revote poll',
	'Summary count'					=> 'Number of poll options',
	'Allow days'					=> 'Run poll for (days)',
	'Votes needed'					=> 'Votes count',
	'Input error'					=> 'You should enter the number of days for the voting, or the count of votes.',
	'Count'							=> 'Count',
	'Button note'					=> 'Update poll',
	'Show poll res'					=> 'Showing poll results',
	'All ch vote'					=> 'Allow to change voting',
	'Max cnt options'				=> 'Count of options can\'t be more than %s.',
	'Min cnt options'				=> 'Count of options can\'t be less than 2.',
	'Days limit'					=> 'Count of poll days can\'t be more than 90 and less than 1.',
	'Votes count'					=> 'Count of votes can\'t be more than 500 and less than 10.',
	'Header note'					=> 'Voting',
	'Options'						=> 'Vote options',
	'Results'						=> 'Voting results: ',
	'No votes'						=> 'There is no votes in this poll yet.',
	'Dis read vote'					=> 'You can\'t see the votes until you vote yourself.',
	'But note'						=> 'Vote',
	'User vote error'				=> 'You have already voted.',
	'End of vote'					=> 'You can\'t vote here as the poll is already ended.',
	'Reset res'						=> 'Reset voting results',
	'Reset res notice'				=> 'Check this if you want to reset voting results.',
	'Remove v'						=> 'Remove voting',
	'Remove v notice'				=> 'Check this if you want to remove voting.',
	'Empty question'				=> 'You should enter a question of poll.',
	'Merge error'					=> 'You can\'t merge these topics, because 2 or more topics include voting. Remove the voting before merging.',
	'Edit voting'					=> 'This two options allow to you reset voting results or remove voting. If you want to edit voting send e-mail to administrator of Forum <a href="mailto:%s">%s</a>.',
	'New voting'					=> 'Using this form, you can create a new voting. The number of possible answers can\'t be less than 2. A question\'s length can\'t be less than 5 symbols. A answer\'s length can\'t be less than 2 symbols.  If you want to add an answer, enter the number of answers you require in the corresponding field and press "Update poll". You can choose the time when your voting will be finished. For this purpose, you can enter a count of days or a maximum count of votes. Remember: if you enter the count of days, the count of votes will be ignored. If your administrator allows to change a user\'s vote or unvoted users can see voting results, you will see appropriate options.',
	'Edit voting admin'				=> 'You can edit voting.'
);
