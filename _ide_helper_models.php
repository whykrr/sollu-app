<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $content_type_id
 * @property string $title
 * @property string $slug
 * @property bool $published
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keyword
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContentType|null $content_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContentFieldValue> $field_values
 * @property-read int|null $field_values_count
 * @method static \Illuminate\Database\Eloquent\Builder|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Content newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Content query()
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereContentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereMetaKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereUpdatedAt($value)
 */
	class Content extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $content_type_id
 * @property string $key
 * @property string $name
 * @property string $field_type
 * @property bool $is_required
 * @property object|null $validation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContentType|null $content_type
 * @property-read \App\Models\ContentFieldValue|null $field_value
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereContentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereFieldType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentField whereValidation($value)
 */
	class ContentField extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $content_id
 * @property int|null $content_field_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContentField|null $content_field
 * @property-read mixed $src
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereContentFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentFieldValue whereValue($value)
 */
	class ContentFieldValue extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $content_id
 * @property string $tag
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Content> $contents
 * @property-read int|null $contents_count
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentTag whereTag($value)
 */
	class ContentTag extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property bool $is_listed
 * @property int|null $max_row
 * @property string|null $title_aliases
 * @property bool $with_meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Content|null $content
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContentField> $content_fields
 * @property-read int|null $content_fields_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Content> $contents
 * @property-read int|null $contents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LocalizedContent> $localized_contents
 * @property-read int|null $localized_contents_count
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType children(int $parent_id)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType parent()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType sidebar()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereIsListed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereMaxRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereTitleAliases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContentType whereWithMeta($value)
 */
	class ContentType extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Language except(\App\Models\Language $language)
 * @method static \Database\Factories\LanguageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereUpdatedAt($value)
 */
	class Language extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $content_type_id
 * @property int $language_id
 * @property string $title
 * @property string $slug
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keyword
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Language|null $language
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereContentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereMetaKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContent whereUpdatedAt($value)
 */
	class LocalizedContent extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $content_id
 * @property int|null $content_field_id
 * @property int $language_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContentField|null $content_field
 * @property-read \App\Models\Language $language
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereContentFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalizedContentFieldValue whereValue($value)
 */
	class LocalizedContentFieldValue extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $read_at
 * @property int|null $read_by
 * @property-read mixed $created_at_full
 * @property-read \App\Models\User|null $reader
 * @property-read \App\Models\MessageResponse|null $response
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Message filters(array $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newest()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereReadBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 */
	class Message extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $message_id
 * @property string $message
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\User|null $responder
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageResponse whereMessageId($value)
 */
	class MessageResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $key
 * @property string|null $value
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property mixed $password
 * @property string $role
 * @property string|null $photo
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageResponse> $message_response
 * @property-read int|null $message_response_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder|User client()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User filter(array $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

