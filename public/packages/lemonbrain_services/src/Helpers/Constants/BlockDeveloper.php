<?php

declare(strict_types=1);

namespace Helpers\Constants;

class BlockDeveloper
{
    public const LINK_TYPE = 'link_type';
    public const LINK_INTERN = 'link_intern';
    public const LINK_HASH_BANG = 'link_hash_bang';
    public const LINK_EXTERN = 'link_extern';
    public const LINK_CONTACT = 'link_contact';
    public const LINK_FILE = 'link_file';
    public const LINK_TITLE = 'link_title';
    public const LINK_SUBJECT = 'link_subject';
    public const LINK_CONTENT = 'link_content';

    public const FIELD_TYPE = 'type';
    public const FIELD_LABEL = 'label';
    public const FIELD_REQUIRED = 'required';
    public const FIELD_SEARCHABLE = 'searchable';
    public const FIELD_CONFIG = 'config';
    public const FIELD_CONFIG_OPTIONS = 'options';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_VIEW = 'view';
    public const FIELD_TYPE_VIEW_SKIP_H = 'skip_h';
    public const FIELD_TYPE_VIEW_NEW_WINDOW = 'new_window';
    public const FIELD_TAB = 'tab';

    public const FIELD_TYPE_SELECT = 'Select';
    public const FIELD_TYPE_PAGE = 'Page';
    public const FIELD_TYPE_HASH_BANG = 'HashBang';
    public const FIELD_TYPE_URL = 'Url';
    public const FIELD_TYPE_FILE = 'File';
    public const FIELD_TYPE_TEXT = 'Text';
    public const FIELD_TYPE_TEXTAREA = 'Textarea';

    public const DESCRIPTION_HASH_BANG = 'Ermöglicht die Navigation auf der Seite selbst (öffnen von Fenstern, Handorgel oder aktivieren von Filtern usw.) und wird am einfachsten aus der URL der Zielseite mit aktivierten Funktion/en kopiert.';
    public const DESCRIPTION_URL = 'Komplette Links inklusive https://lemonbrain.ch.';
    public const DESCRIPTION_LINK = 'komplette E-Mail Adresse oder Telefonnummer';
    public const DESCRIPTION_TEXT = 'Wenn dieses Feld leer ist, wird der Datei-, Seitenname oder Link als Titel angezeigt.';
    public const DESCRIPTION_SUBJECT = 'Nur bei der Verknüpfung mit einer E-Mail-Adresse. Wenn dieses Feld leer ist, ist der Betreff der E-Mail-Adresse leer.';
    public const DESCRIPTION_CONTENT = 'Nur bei der Verknüpfung mit einer E-Mail-Adresse. Wenn dieses Feld leer ist, ist der Inhalt der E-Mail-Adresse leer.';

    public const LABEL_SELECT = 'Link Typ';
    public const LABEL_PAGE = 'Link Seite';
    public const LABEL_HASH_BANG = 'Link Parameter';
    public const LABEL_URL = 'Link';
    public const LABEL_LINK = 'Kontakt-Link';
    public const LABEL_SUBJECT = 'E-Mail-Betreff';
    public const LABEL_CONTENT = 'E-Mail-Inhalt';
    public const LABEL_FILE = 'Link Datei';
    public const LABEL_TEXT = 'Link Titel';
}
