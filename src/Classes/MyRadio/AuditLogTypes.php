<?php

namespace MyRadio\MyRadio;

class AuditLogTypes
{
    public const Created = 'created';
    public const Edited = 'edited';

    public const MetaUpdated = 'meta_updated';

    public const Scheduled = 'scheduled';
    public const Rejected = 'rejected';
    public const Cancelled = 'cancelled';
    public const SeasonCancelled = 'season_cancelled';
    public const Moved = 'moved';
}
