<?php
// + --------------------------------------------------------------------------+
// | evList une solution de calendrier pour glFusion |
// + --------------------------------------------------------------------------+
// | english_utf-8.php |
// | |
// | Anglais langue de fichier evList |
// + --------------------------------------------------------------------------+
// | basé sur le plugin evList pour CMS Geeklog |
// | Copyright (C) 2007 par les auteurs suivants : |
// | |
// | Auteurs : Alford Deeley - ajdeeley À summitpages.ca |
// + --------------------------------------------------------------------------+
// | |
// | Ce programme est un logiciel libre ; vous pouvez le redistribuer et/ou |
// | modifier selon les termes de la Licence Publique Générale GNU |
// | publiée par la Free Software Foundation ; version 2 |
// | du Licence, ou encore (à votre choix) toute version ultérieure. |
// | |
// | Ce programme est distribué dans l'espoir qu'il sera utile, |
// | mais SANS AUCUNE GARANTIE ; sans même la garantie implicite de |
// | QUALITÉ MARCHANDE ou D'ADÉQUATION À UN USAGE PARTICULIER. Reportez-vous à la |
// | Licence Publique Générale GNU pour plus de détails. |
// | |
// | Vous devez avoir reçu une copie de la Licence Publique Générale GNU |
// | avec ce programme; si ce n'est pas le cas, écrivez à la Free Software Foundation, |
// | Inc. , 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
// | |
// + --------------------------------------------------------------------------+
/**
* anglais fichier de langue pour le evList plugin
* @package evlist
*/
// ce fichier ne peut pas être utilisé sur son propre
if ( !defined ( 'GVERSION')) {
    die ( 'Ce fichier ne peut pas être utilisé sur son propre. ' );
}
global $_EV_CONF;
global $_CONF;

$LANG_EVLIST = array(
'pi_title'          => 'Calendrier des événements',
'moderation_title'      => 'événement mémoires',
'del_future'            => 'Supprimer cette et instances futures',
'conf_del_future'       => 'Êtes-vous sûr de vouloir supprimer toutes les futures instances de cette événement? ',
'edit_future'           => 'modifier cette et futures instances',
'del_all'           => 'Supprimer cet événement',
'conf_del_all'          => 'Êtes-vous sûr de vouloir supprimer toutes les occurrences de cet événement? ',
'del_repeat'            => 'Supprimer cette occurrence',
'conf_del_repeat'       => 'Êtes-vous sûr de vouloir supprimer cette occurrence? ',
'conf_del_event'        => 'Etes-vous sûr de vouloir supprimer cet événement? ',
'conf_del_item'         => 'Êtes-vous sûr de vouloir supprimer cet élément ? ',
'edit_repeat'           => 'Modifier cette instance',
'edit_event'            => 'Modifier l événement',
'add_event'             => 'Ajouter un événement',
'editcal'               => 'Edit Calendar',
'editcat'               => 'Edit Category',
'editticket'        => 'Edit Ticket Type',
'err_missing_title'         => 'un titre est requis. ',
'err_missing_weekdays'      => 'Vous devez spécifier au moins un jour pour un jour de semaine récurrence. ',
'err_times'             => ' L heure de fin ne peut pas être avant l heure de départ. ',
'err_db_saving'         => 'une erreur de base de données est survenue lors de l enregistrement de votre dossier. ',
'err_cal_import'        => 'Il y a eu %d erreurs importation à partir du calendrier. Vérifier votre journal d erreurs pour plus de détails',
'err_import_event'      => 'Erreur d importation événement %s',
'err_cal_notavail'      => 'le calendrier plugin les données ne sont pas disponibles. ',
'err_upd_repeats'   => 'Error updating repeat events',
'info'              => 'Information',
'warning'           => 'Avertissement',
'alerte'            => 'Alerte',
'editing_instance'      => 'Vous êtes en train de modifier une instance unique de cet événement. ',
'editing_future'        => 'votre édition toutes les futures instances de cet événement. ',
'editing_series'        => 'vous modifiez l événement série. Les personnalisations apportées aux instances spécifiques seront perdues! ',
'day'               => 'All Day',
'recur_cust_format'         => ' (format : AAAA-MM-jj, aaaa-MM-JJ, etc. ) . ',
'recur_cust_ignoredates'    => ' (ignore les dates de début et de fin indiquées ci-dessus. ) ',
'click_to_select'       => 'Cliquez pour sélectionner',
'access_denied'         => 'Accès refusé',
'skip_weekends'         => 'Ignorer les week-ends? ',
'yes'               => 'Oui',
'no'               => 'No',
'next_bus_day'          => 'jour ouvrable suivant',
'modifier'          => 'Edit',
'event_title'           => 'Event Title',
'event_summary'         => 'Résumé événement',
'start_date'            => 'Date de début',
'start_time'            => 'Start Time',
'heure_fin'             => 'Heure de fin',
'end_date'          => 'Date de Fin',
'copy'            => 'Copier',
'id'                => 'ID',
'enabled'            => 'Activé',
'enabled_q'             => 'Activé? ',
'ical_enabled'          => 'ical activé',
'calendar'            => 'Calendrier',
'calendars'           => 'calendriers',
'select_cals'           => 'Sélectionner les calendriers seront affichées',
'new_calendar'          => 'Nouveau calendrier',
'events'            => 'événements',
'new_event'             => 'Nouveau  Événement',
'categories'            => 'Catégories',
'category'             => 'Catégorie',
'new_category'          => 'Nouvelle catégorie',
'tickettypes'      => 'Ticket Types',
'type'              => 'Type',
'fee'               => 'Fee',
'new_ticket_type'   => 'New Ticket Type',
'print_tickets'     => 'Print Tickets',
'print_my_tickets'  => 'Print My Tickets',
'required'          => 'Required',
'import'            => 'Import',
'import_calendar'       => 'Importer à partir de Calendrier',
'import_from_csv'       => 'Importer de CSV',
'title'             => 'titre',
'ev_info'           => 'événement Informations',
'ev_schedule'           => 'Calendrier',
'ev_perms'          => 'Permissions',
'ev_contact'            => 'Contact',
'ev_location'           => 'Emplacement',
'show_upcoming'         => 'Show dans les prochains événements',
'show_cb'           => 'Show in Centerblocks',
'misc'              => 'Divers',
'foreground'            => 'Foreground',
'background'            => 'Historique',
'colors'          => 'Couleurs',
'cal_name'          => 'nom de calendrier',
'cat_name'          => 'Nom de la catégorie',
'reset'             => 'Réinitialiser le formulaire',
'del_cal_msg1'          => "Vous êtes sur le point de supprimer une Calendrier. Il s'agit d'une suppression permanente et ne peut pas être inversée. Assurez-vous que c'est ce que vous voulez faire avant de cliquer sur 'Soumettre' ci-dessous!",
'del_cal_events'        => 'Ce calendrier a %d événement(s) associés. Vous pouvez déplacer ces événements à un autre calendrier, en sélectionnant un ci-dessous. Si vous ne sélectionnez pas un nouveau calendrier pour les événements, ils seront tous supprimés définitivement de la base de données. ',
'confirm_del'           => "Confirmer que vous souhaitez supprimer l'élément",
'none_delete'           => 'None- supprimer les événements',
'deleting_cal'          => 'Suppression de calendrier',
'rec_formats'           => array (
0   => 'Does not recur',
1               => 'quotidiennement par date, par ex., Avril 4 thru 7 avril (séquentiel) ',
2               => 'Mensuellement par date (les mêmes dates chaque mois) ',
3               => 'chaque année, par date, par ex., décembre 25e année après année',
4               => 'hebdomadaire par jour, par ex., LUN, mer et ven',
5               => 'Mensuelle par jour, par ex., le 3ème vendredi de chaque mois',
6               => 'dates personnalisées : une liste délimitée par des virgules, des dates du calendrier', ),

'rec_periods'           => array(
0 => '',
1               => 'jour',
2               => 'mois',
3               => 'Année',
4               => 'semaine',
5               => 'mois',
6               => '', ),

'postmodes'             => array (
'plaintext'          => 'en clair',
'html'              => 'html', ),

'rec_intervals'         => array (
1               => 'premier',
2               => 'Second',
3               => 'tiers',
4               => 'Quatrième',
5               => 'Dernier', ),

'ranges'            => array (
1               => 'passé',
2               => 'venir',
3               => 'cette semaine',
4               => 'ce mois', ),

'periods'          => array(
'day'              => 'Jour',
'week'           => 'Semaine',
'month'              => 'Mois',
'year'             => 'Année',
'agenda'  => 'Agenda',
),


'filter'            => 'Filtre',
'when'             => 'Quand' ,
'where'                => 'où',
'what'                => 'CE',
'click_here' => "<a href=\"%s\" %s>cliquez ici</a> pour plus d'informations",
'more_info'             => 'plus d informations',
'contact_us'            => 'Veuillez <a href="%s">nous contacter< /a> pour plus d informations.',
'rem_title' => 'Event Reminder',
'rem_subject' => 'Reminder: %s',
'rem_footer1'          => "Vous recevez ce Rappel de l'événement parce que votre adresse a été présenté à {$_CONF['site_name']}.",
'rem_footer2'  => 'This is a one-time message. You will not receive another message unless you subscribe to other events.',

'rem_url'           => "Pour plus d'informations, consultez %s",
'you_are_subscribed'        => 'Vous êtes abonné à cet événement. ',
'topic_all'             => 'Tous',
'topic_home'            => 'accueil seulement',
'recur_desc'            => array(
1               => 'survient chaque jour',
2               => 'se produit à la même date chaque mois',
3               => 'se produit à la même date chaque année',
4               => 'Se produit tous les %intervalle% semaine sur %day% ',
5               => 'Se produit %intervalle% mois sur le %daynum% %date% ',
6               => '',
),

'on_days'           => 'sur %s',
'on_the_days'           => 'sur le %s',
'chacun'            => 'chaque',
'every_num'             => 'tous les %d',
'Recur_stop_desc'       => "jusqu'à %s",
'recur_freq_txt'        => 'survient chaque',
'interval_label'        => "Spécifier l'intervalle et jour de cet événement se reproduira",
'Interval_note'         => 'la première occurence sera à la date indiquée ci-dessus. ',
'all_calendars'         => 'Tous les calendriers',
'all_categories'        => 'toutes les catégories',
'update_cats'           => 'Update catégories',
'notify_submission'         => "un nouveau cas a été soumis à {$_CONF['site_name']}. Il peut être approuvé ou supprimés à {$_CONF['site_admin_url']}/modération.php.",
'submitted_by'          => 'Soumis par',
'notify_subject'        => "Notification d'événement de {$_CONF['site_name']}",
'show_contactlink'      => 'Afficher le lien de contact e-mail',
'no_match'          => "Il n'y a aucun événement ne correspond à vos critères.",
'event_begins'          => 'Cet événement commence',
'read_more'             => 'Lire plus',
'quick_del'             => 'Suppression rapide',
'gen_evt_info'          => "Informations d'événement",
'full_desc'             => 'Description complète',
'postmode'          => 'Postmode',
'post_html_note1'       => "REMARQUE : La <i>Emplacement de l'événement< /i> champ ci-dessous également accepte html.",
'enable_reminders_q'        => 'activer des rappels par e-mail? ',
'disable_reminders_note'    => 'REMARQUE : La désactivation des rappels pour supprimer tous les rappels mémorisés. ',
'submit_email_note'         => "Soumettre votre adresse e-mail afin d'être rappelé de cet événement avant son apparition. ",
'add_to_cats'           => 'ajouter votre événement à un seul ou à plusieurs Catégories',
'cats_not_req'          => "Ajout de votre événement à une catégorie n'est pas requis. ",
'cat_note1'             => 'Créer une nouvelle catégorie pour votre événement Au lieu de, ou en plus des catégories existantes. ',
'url'               => 'URL',
'street_address'        => 'Adresse',
'city'              => 'ville',
'state'              => 'Province/État',
'country'              => 'pays',
'zip'               => 'Code Postal',
'for_more_info'         => 'pour plus de renseignements, contacter',
'e-mail'            => 'E-mail',
'téléphone'             => 'n° de téléphone ',
'perms_desc'            => 'autorisations : (R = Lire, E = editer, modifier les droits supposent droits de lecture) ',
'date_time_info'        => "Informations de date et d'heure",
'split_q'           => 'Split? ',
'rec_event_info'        => 'Événement récurrent Informations',
'rec_event_q'           => 'Est-ce un événement récurrent? ',
'event_recurs'          => 'événement réapparaît',
'select_format'         => 'Sélectionnez Format',
'Jump_today'            => "Sauter à aujourd'hui",
'day_view'          => 'Daily View',
'week_view'             => 'affichage hebdomadaire',
'month_view'            => 'affichage mensuel',
'year_view'             => 'annuelle View',
'agenda_view'             => 'Affichage liste',
'select_range'          => "Sélectionnez un événement gamme d'affichage",
'or_choose_cat'         => 'Et/ou choisissez une catégorie',
'aller'             => 'Go',
'days_prior'            => 'jours avant cet événement. ',
'email_private'         => 'votre email restera confidentiel et ne sera utilisé que pour vous rappeler de cet événement. ',
'messages'          => array(
1               => 'succès ! Événement a été supprimé. ',
2               => 'succès ! Votre événement a été enregistrée. ',
3               => 'événement a été copié. Vous pouvez maintenant modifier votre nouvel événement. ',
4               => 'succès ! Événement a été mis à jour. ',
5               => 'champs obligatoires sont vides. Veuillez revérifier votre soumission. ',
6               => 'Alerte! ',
7               => 'evList paramètres par défaut ont été appliqués. ',
8               => 'evList paramètres de configuration ont été mises à jour. ',
9               => "Merci de présenter votre cas à {$_CONF['site_name']}. Il a été soumis à notre personnel pour approbation. Si elle est approuvée, votre événement sera disponible pour les autres à lire sur notre site. ",
10              => 'fourni les dates ne sont pas valides. Veuillez revérifier votre soumission. ',
11              => 'catégories ont été mises à jour. ',
12              => 'Rappel enregistré. Vous devriez recevoir un e-mail de rappel avant cet événement. ',
13              => 'Vous avez fourni un invalide ou incorrectement formaté adresse e-mail. Veuillez réessayer. ',
14              => "Vous devez spécifier le nombre de jours avant l'événement que vous souhaitez être notifié. ",
17              => 'Le glFusion événements de calendrier ont été importés',
18              => 'supprimé notification de rappel',
19              => "une ou plusieurs erreurs se sont produites pendant l'importation de l'agenda. Vérifier le journal des erreurs pour plus de détails. ",
20              => "Cet événement n\ 't permettre les inscriptions, ou vous n'y avez pas accès. ",
21              => 'vous\pleines déjà signé pour cet événement. ',
22              => 'Cet événement est plein. ',
23              => 'Il y a eu une erreur de traitement de votre demande. ',
24              => 'Vous avez été enregistré pour cet événement. ',
25              => 'Votre inscription a été annulée. ',
    26  => 'Payment is required, click <a href="%s">here</a> to check out',
    27  => 'This event is full and you have been added to the waiting list',
    28  => 'You have %d tickets remaining.',
    50  => 'Not Paid',
    51  => 'Already Used',
),

'admin_instr'           => array(
'categories'            => "Suppression de catégories <strong>pas< /strong> supprimer les événements appartenant à ces catégories. <br / >La désactivation d'une catégorie <strong>pas< /strong> désactiver ses événements. Ces événements continueront d'apparaître dans la liste d'événements. ",
'calendars'            => "Tous les événements doivent être associées à un calendrier. <br / >La désactivation d'un calendrier empêche ses événements d'être affiché. La suppression d'un calendrier, il faut que les événements appartenant à la déplacer dans un autre calendrier. <br / >Calendrier numéro 1 ne peut pas être supprimé, mais elle peut être désactivée. ",
'events'            => "Pour créer un nouvel événement, cliquez sur 'Nouvel événement' ci-dessus. <br / >Pour modifier ou supprimer un événement, cliquez sur cet événement\'s modifier icône ci-dessous. Pour activer/désactiver un événement, cochez la case appropriée ci-dessous. ",
    'tickettypes' => 'Tickets can be created for free or paid admission, and to cover one event occurrence or all occurrences (event pass). Tickets are only used if the global &quot;Enable RSVP&quot; setting is enabled.<br />Ticket Types can only be deleted if they haven&apos;t been used for any events.',
),

'current_events'        => 'événements en cours',
'past_events'           => 'événements du passé',
'upcoming_events'       => 'Événements à venir',
'this_week_events'      => 'cette semaine\'s événements',
'this_month_events'         => 'ce mois\'s événements',
'hits'              => 'Hits',
'top_ten'           => 'Top dix evList événements',
'no_events_viewable'        => 'aucun événement dans le système sont actuellement affichable. ',
'date'              => 'Date',
'time'          => 'Time',
'all_upcoming'          => 'Tous les événements à venir',
'subscribe_to'          => 'abonner à',
'subscribe'     => 'Subscribe',
'event_editor'          => "Éditeur d'événement",
'datestart_note'        => " * année de départ et le mois sont des champs requis. ",
'custom_label'          => 'Spécifier %s sur laquelle cet événement se reproduira%s',
'stop_label'            => 'Spécifier le %s au-delà  Que cet événement ne se répète pas%s',
'day_by_date'           => 'jour, par date,',
'year_and_month'        => 'année et mois',
'year'             => 'année',
'days_of_week'          => 'jours de la semaine',
'date_l'            => 'Date',
'all_day_event'         => " C'est un événement de toute une journée. ",
'more_from_cat'         => "Plus d'événements de: ",
'access_denied_msg'         => "Seuls les utilisateurs autorisés ont accès à cette page. Votre nom d'utilisateur et IP ont été enregistrées. ",
'coordinates'             => 'coordonne',
'latitude'          => 'Latitude',
'longitude'             => 'Longitude',
'instr_coords'          => "si zéro ou vide, les coordonnées seront automatiquement renseigné à partir des informations d'adresse, si possible. ",
'select_location'       => 'Sélectionnez Emplacement',
'instr_sel_loc'         => 'Sélectionnez un emplacement dans la liste, ou remplissez les détails. ',
'use_rsvp'          => 'Activer inscriptions? ',
'max_rsvp'          => 'Max. Les participants',
'max_user_rsvp' => 'Max. Registrations per User',
'signup'            => 'vous inscrire à cet événement',
'cancelreg'             => 'annuler votre inscription',
'rsvp_none'             => 'inscriptions Désactivé',
'rsvp_event'            => "Autoriser inscriptions pour l'événement",
'rsvp_repeat'           => 'Autoriser inscriptions pour chaque occurrence',
'rsvp_mindays'          => 'Min. jours de RSVP',
'admin_rsvp'            => "gérer RSVP\ 's ",
'rsvp_date'             => "Date d'enregistrement",
'rsvp_waitlist'         => "Accepter sur liste d'attente réserves? ",
'rsvp_cutoff'           => 'RSVP Cutoff (jours) ',
'registration'  => 'Registration',
'rsvp_waitlist' => 'Accept waitlisted reservations?',
'rsvp_cutoff'   => 'RSVP Cutoff (days)',
'sel_monthdays'         => "Sélectionnez les jours chaque mois lorsque l'événement se produira",
'sub_this_instance'         => 'cette Instance',
'sub_all_instances'         => 'toutes les occurrences',
'description'   => 'Description',
'event_pass'    => 'Event Pass',
'cancel_free'   => 'Free registrations can be cancelled here if you will not be attending.',
'free_rsvp'     => 'Free Registrations',
'ticket_num'    => 'Ticket Number',
'date_used'     => 'Date Used',
'paid'          => 'Paid',
'balance_due'   => 'Balance Due',
'login_to_register' => 'You need to log into the site to register for this event',
'conf_reset'    => 'Are your sure you want to reset this item?',
'reset_usage'   => 'Reset Usage',
'export_list'   => 'Export List',
'waitlisted'    => 'Waitlisted',
'name'          => 'Name',
'quantity'      => 'Quantity',
'alert' => 'Alert',
'allday' => 'All Day',
'edit' => 'Edit',
'end_time' => 'End Time',
'clk_help' => 'Click for help',
'each' => 'each',
'recur_stop_desc' => ' until %s',
'interval_note' => 'The first occurance will be on the date specified above.',
'email' => 'E-mail',
'phone' => 'Phone #',
'jump_today' => 'Jump to Today',
'go' => 'Go',
'all' => 'All',
'sel_category' => 'Select Category',
'click_for_datepicker' => 'Click for Date Selector',
'paid_only'     => 'Paid Only',
'paid_or_unpaid'    => 'Paid or Unpaid',
'register'      => 'Register',
'allow_ticket_printing' => 'Allow Ticket Printing',
'enable_comments' => 'Enable Comments?',
'closed'        => 'Closed',
'event'         => 'Event',
'timezone'      => 'Timezone',
'tz_local'      => 'Guest&apos;s local timezone',
'tz_select'     => 'Select Timezone',
'msg_item_updated' => 'Item has been updated',
'msg_item_nochange' => 'Item was not changed',
'print'         => 'Print',
'instr_import_cal' => 'Import calendar events from the glFusion Calendar plugin into Evlist. This function should normally be used only once, but events with the same event ID are not imported to guard against duplicates.',
'sample'        => 'Sample',
'icon'          => 'Icon',
'inherit'       => 'Inherit',
'orderby'       => 'Order',
'show_after'    => 'Show After',
'first'         => 'First',
'ev_not_found'  => 'The requested event was not found.',
'jump'          => 'Jump',
'no_tickets_print' => 'There are no tickets qualified for printing.',
'comment' => 'Comment',
'enter_comments' => 'Enter Comments',
'tic_cmt_allowed' => 'Ticket Comments Allowed',
'tic_cmt_prompts' => 'Ticket Comment Prompts',
'tic_cmt_view_grp' => 'RSVP View Group',
'signup_list' => 'Signup List',
'at_dscp_event' => 'Create a link to an event or the closest upcoming instance.',
'at_dscp_evlist_signups' => 'Show a table of signups to authorized users.',
'today' => 'Today',
'back_to_cal' => 'Back to Calendar',
'my_events' => 'My Events',
'print_cal' => 'Print Calendar',
'status' => 'Status',
'disabled' => 'Disabled',
'cancelled' => 'Cancelled',
'if_any'        => ', if any',
'dates'         => 'Dates',
'shortcode'     => 'Short Code',
'free_caps'     => 'FREE',
'click_for_details' => 'Click for details.',
'logo_image' => 'Logo Image',
);

$LANG_EVLIST_HELP = array(
'calendar' => 'Select the calendar where this event will appear. Calendars can be included or excluded from views and feeds.',
'ev_title' => 'Enter the title for this event. This text will appear in most calendars as a hover link to the event summary.',
'ev_summary' => 'Enter a fairly short description of the event. This will appear on the event display, and also when a user hovers the mouse over the event title in calendar views.',
'ev_dscp' => 'Enter an optional detailed description of this event. This text appears only on the event detail page.',
'ev_url' => 'Enter an optional URL for the event, such as a link to a site article or an external web page. You may use <b>%site_url%</b> as a placeholder for the site URL.',
'ev_enabled' => 'Check this box to enable the event. Events can be temporarily hidden from view without deleting them.',
'ena_reminders' => 'If this box is checked, users can enter an email address to have a reminder sent to them a number of days before the event.',
'sel_categories' => 'Categories are optional and a way to relate events together. Select one or more existing categories by checking their checkboxes, or create a new category by entering some text in the provided field.',
'split' => 'Check this box if the event is split into two times each day, e.g. 9:00am - 11:00am and 1:00pm - 4:00pm. If this box is checked, additional fields will appear where you can enter the starting and ending times for the second session.',
'startdt' => 'Enter the start date either by entering the text as a SQL-formatted date (YYYY-MM-DD), or by clicking the calendar icon and browsing to the date. Select the starting time by using the dropdown selections.',
'enddt' => 'Enter the ending date either by entering the text as a SQL-formatted date (YYYY-MM-DD), or by clicking the calendar icon and browsing to the date. Select the ending time by using the dropdown selections.',
'timezone' => 'If the &quot;local timezone&quot; checkbox is unchecked, you can select a timezone for this event which will be displayed next to the date/time information. If the local timezone is checked then no timezone will be shown; this is the same as previous versions of evList.',
'cal_name' => 'Enter a name for this calendar. Names should be unique.',
'cal_colors' => 'Select the foreground and background colors used to display events for this calendar within calendar views. Check the &quot;Inherit&quot; checkbox to have the color inherited from the parent elements.',
'cal_icon' => 'Enter the name of a UIKit icon to be shown with events in this calendar. Enter only the icon name, e.g. &quot;circle&quot;',
'cal_enabled' => 'Check to enable this calendar. Disabled calendars will not show in views nor in the event submittion form.',
'cal_ical_ena' => 'Check to allow ICal subscriptions to this calendar.',
'owner' => 'Select the owner for this item.',
'group' => 'Select the group associated with this item',
'perms' => 'Set the permissions for this item.',
'event_pass' => 'Checked if this ticket type is a full event pass.',
'del_hdr1' => 'Some items are reserved for system use and cannot be deleted.',
'rec_seq_days' => 'The event will occur on each day from the start date through the provided end date.',
'prt_tickets_btn' => 'Print all non-waitlisted tickets.',
);

$PLG_evlist_MESSAGE1         = "Cet événement n\ 't permettre les inscriptions, ou vous n'avez pas accès. ";
$PLG_evlist_MESSAGE2         = "Vous\pleines déjà signé pour Cet événement. ";
$PLG_evlist_MESSAGE3         = 'Cet événement est plein. ';
$PLG_evlist_MESSAGE4         = 'il y a eu une erreur de traitement de votre demande. ';

//la localisation de l'Admin INTERFACE UTILISATEUR DE Configuration
$LANG_configsections[ 'evlist'] = array(
'label'             => 'evList',
'title'                 => 'evList Configuration'
);

$LANG_confignames[ 'evlist'] = array(
'allow_html'            => "Autoriser html lors de l'imputation? ",
'usermenu_option'       => 'User menu option lien',
'enable_menuitem'       => "Activer l'élément de menu? ",
'week_begins'           => 'Semaine commence sur',
'date_format'           => 'format de date',
'time_format'           => "format de l'heure",
'enable_categories'         => 'Activer catégories',
'enable_centerblock'        => 'Activer centerblock? ',
'pos_centerblock'       => 'Centerblock position',
'topic_centerblock'         => 'Sujet',
'range_centerblock'         => 'Sélectionnez un événement gamme Pour afficher',
'limit_centerblock'         => "Entrez le nombre d'événements à afficher",
'limit_list'            => "liste principale : nombre d'événements à afficher par page",
'limit_block'           => "événements à venir bloquer : nombre d'événements à afficher",
'limit_summary'         => "Nombre de caractères à afficher dans le résumé d'événements",
'enable_reminders'      => "activer des rappels par e-mail? ",
'event_passing'         => "un événement cesse d'être <i>prochaine< /i> ",
'default_permissions'       => 'Les autorisations par défaut (propriétaire,groupe,membres,anon) ',
'reminder_days'         => 'Nombre de jours avant un événement pour permettre des rappels',
'displayblocks'         => 'Display Blocks in List Views',
'displayblocks_blk'     => 'Display Blocks in Month/Year Views',
'default_view'          => 'Vue par défaut',
'max_upcoming_days'         => 'Max. Les prochains jours à afficher dans la liste',
'use_locator'           => 'intégrer avec le Locator plugin ? ',
'use_weather'           => 'intégrer avec la météo plugin ? ',
'enable_rsvp'           => 'Enable Registration/Ticketing?',
'rsvp_print'            => 'Enable Ticket Printing?',
'commentsupport'        => 'Enable Comments?',
'submission_queue'      => 'Use submission queue?',
'ticket_format'         => 'Ticket Format String',
'pi_cal_map'            => 'Plugin-Calendar Mapping',
    'cb_dup_chk'            => 'Key to hide duplicate instances',
    'cb_hide_small'         => 'Hide on small screens',
'rec_seq_days' => 'The event will occur on each day from the start date through the provided end date.',
'prt_tickets_btn' => 'Print all non-waitlisted tickets.',
    'purge_cancelled_days'  => 'Days after which to purge cancelled events',
    'ical_range'            => 'ICal feed range of days (to,from)',
);

$LANG_configsubgroups[ 'evlist'] = array(
    'sg_main'               => 'Paramètres principaux',
    'sg_rsvp'               => 'RSVP/Ticketing',
    'sg_integ'              => 'Integrations',
);

$LANG_fs[ 'evlist'] = array(
'ev_main'               => 'General Settings',
'ev_gui'            => 'paramètres GUI',
'ev_centerblock'        => 'Centerblock Settings',
'ev_permissions'        => 'Les autorisations par défaut',
'ev_rsvp'               => 'Registration and Ticketing',
'ev_integ_other'        => 'Other',
);

$LANG_configSelect[ 'evlist'] = array(
    0 => array(
        1 => 'true',
        2 => 'false',
    ),
    2 => array(
        0 => 'None',
        1 => 'Ajouter événement',
        2 => 'liste les événements',
    ),
    6 => array(
        1 => "dès que l'heure de début A adopté (si existe) ",
        2 => "dès que la date de début n'est pas écoulé, ie, le jour suivant,",
        3 => "dès que l'heure de fin a adopté (si il existe) ,",
        4 => 'dès que la date de fin a été dépassée,',
    ),
    7 => array(
        1 => 'Haut de page',
        2 => 'après une…',
        3 => 'Bas de page',
        4 => 'page entière',
    ),
    8 => array(
        1 => 'passé',
        2 => 'venir',
        3 => 'cette semaine',
        4 => 'ce mois',
    ),
    9 => array(
        0 => 'Disabled',
        1 => 'Table',
        2 => 'Story',
        3 => 'Calendar',
    ),
    10 => array(
        '' => 'Show all occurrences',
        'rp_ev_id' => 'Master Event Record',
        'rp_id' => 'Recurring Event Instance',
    ),
    12 => array(
        0 => 'Aucun accès',
        2 => 'Lecture seule',
        3 => 'lecture-ecriture',
    ),
    13 => array(
        0 => 'None',
        1 => 'Gauche Blocs',
        2 => 'droit Blocs',
        3 => 'gauche & droit bloque',
    ),
    14 => array(
        'day' => 'Jour',
        'week' => 'Semaine',
        'month' => 'Mois',
        'year' => 'année',
        'agenda' => 'Liste',
    ),
);

