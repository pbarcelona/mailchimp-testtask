<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use EoneoPay\Utils\Str;
use App\Database\Entities\MailChimp\MailChimpList;

/**
 * @ORM\Entity()
 */
class MailChimpListMember extends MailChimpEntity
{
    /**
     * @ORM\Column(name="email_address", type="string")
     *
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(name="email_type", type="string", nullable=true)
     *
     * @var string
     */
    private $emailType;

    /**
     * @ORM\Column(name="status", type="string")
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $memberId;

    /**
     * @ORM\Column(name="mail_chimp_member_id", type="string", nullable=true)
     *
     * @var string
     */
    private $mailChimpMemberId;

    /**
     * @ORM\Column(name="list_id", type="guid")
     *
     * @var string
     */
    private $listId;

    /**
     * @ORM\Column(name="merge_fields", type="array", nullable=true)
     *
     * @var array
     */
    private $mergeFields;

    /**
     * @ORM\Column(name="interests", type="array", nullable=true)
     *
     * @var array
     */
    private $interests;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="vip", type="boolean", nullable=true)
     *
     * @var bool
     */
    private $vip;

    /**
     * @ORM\Column(name="location", type="array", nullable=true)
     *
     * @var array
     */
    private $location;

    /**
     * @ORM\Column(name="marketing_permissions", type="array", nullable=true)
     *
     * @var array
     */
    private $marketingPermissions;

    /**
     * @ORM\Column(name="ip_signup", type="string", nullable=true)
     *
     * @var string
     */
    private $ipSignup;

    /**
     * @ORM\Column(name="ip_opt", type="string", nullable=true)
     *
     * @var string
     */
    private $ipOpt;

    /**
     * @ORM\Column(name="tags", type="array", nullable=true)
     *
     * @var array
     */
    private $tags;

    /**
     * Get id.
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->memberId;
    }

    /**
     * Get list id of the member.
     *
     * @return null|string
     */
    public function getListId(): ?string
    {
        return $this->listId;
    }

    /**
     * Get mailchimp member id of the list.
     *
     * @return null|string
     */
    public function getMailChimpMemberId(): ?string
    {
        return $this->mailChimpMemberId;
    }

    /**
     * Get validation rules for mailchimp member entity.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'email_address' => 'required|string',
            'email_type' => 'nullable|string|in:email,text',
            'status' => 'required|string|in:subscribed,unsubscribed,cleaned,pending',
            'merge_fields' => 'nullable|array',
            'interests' => 'nullable|array', // The key of this object’s properties is the ID of the interest in question.
            'language' => 'nullable|string|in:en,ar,af,be,bg,ca,zh,hr,cs,da,nl,et,fa,fi,fr,fr_CA,de,el,he,hi,hu,is,id,ga,it,ja,km,ko,lv,lt,mt,ms,mk,no,pl,pt,pt_PT,ro,ru,sr,sk,sl,es,es_ES,sw,sv,ta,th,tr,uk,vi', // these were from mailchimp language specifications.
            'vip' => 'nullable|boolean',
            'location' => 'nullable|array',
            'location.longitude' => 'nullable|numeric',
            'location.latitude' => 'nullable|numeric',
            'marketing_permissions' => 'nullable|array',
            'marketing_permissions.*.marketing_permission_id' => 'nullable|string',
            'marketing_permissions.*.enabled' => 'nullable|boolean',
            'ip_signup' => 'nullable|ip',
            'ip_opt' => 'nullable|ip',
            'tags' => 'nullable|array',
        ];
    }

    /**
     * Set email address.
     *
     * @param string $emailAddress
     *
     * @return MailChimpListMember
     */
    public function setEmailAddress(string $emailAddress): MailChimpListMember
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Set email type.
     *
     * @param string $emailType
     *
     * @return MailChimpListMember
     */
    public function setEmailType(string $emailType): MailChimpListMember
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Set mailchimp member id.
     *
     * @param string $mailChimpMemberId
     *
     * @return MailChimpListMember
     */
    public function setMailChimpMemberId(string $mailChimpMemberId): MailChimpListMember
    {
        $this->mailChimpMemberId = $mailChimpMemberId;

        return $this;
    }    

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return MailChimpListMember
     */
    public function setStatus(string $status): MailChimpListMember
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set merge fields.
     *
     * @param array $mergeFields
     *
     * @return MailChimpListMember
     */
    public function setMergeFields(array $mergeFields): MailChimpListMember
    {
        $this->mergeFields = $mergeFields;

        return $this;
    }

    /**
     * Set interests.
     *
     * @param array $mergeFields
     *
     * @return MailChimpListMember
     */
    public function setInterests(array $interests): MailChimpListMember
    {
        $this->interests = $interests;

        return $this;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return MailChimpListMember
     */
    public function setLanguage(string $language): MailChimpListMember
    {
        $this->language = $language;

        return $this;
    }    

    /**
     * Set vip.
     *
     * @param bool $vip
     *
     * @return MailChimpListMember
     */
    public function setVip(bool $vip): MailChimpListMember
    {
        $this->vip = $vip;

        return $this;
    }

    /**
     * Set location.
     *
     * @param array $location
     *
     * @return MailChimpListMember
     */
    public function setLocation(array $location): MailChimpListMember
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Set marketing permissions.
     *
     * @param array $marketingPermissions
     *
     * @return MailChimpListMember
     */
    public function setMarketingPermissions(array $marketingPermissions): MailChimpListMember
    {
        $this->marketingPermissions = $marketingPermissions;

        return $this;
    }

    /**
     * Set IP Signup.
     *
     * @param string $ipSignup
     *
     * @return MailChimpListMember
     */
    public function setIpSignup(string $ipSignup): MailChimpListMember
    {
        $this->ipSignup = $ipSignup;

        return $this;
    }  

    /**
     * Set IP Opt in.
     *
     * @param string $ipOpt
     *
     * @return MailChimpListMember
     */
    public function setIpOpt(string $ipOpt): MailChimpListMember
    {
        $this->ipOpt = $ipOpt;

        return $this;
    }  

    /**
     * Set marketing permissions.
     *
     * @param array $tags
     *
     * @return MailChimpListMember
     */
    public function setTags(array $tags): MailChimpListMember
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Set mailchimp list id of the member.
     *
     * @param MailChimpList $list
     *
     * @return \App\Database\Entities\MailChimp\MailChimpListMember
     */
    public function setList(MailChimpList $list): MailChimpListMember
    {
        $this->listId = $list->getId();

        return $this;
    }

    /**
     * Get array representation of entity.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $str = new Str();

        foreach (\get_object_vars($this) as $property => $value) {
            $array[$str->snake($property)] = $value;
        }

        return $array;
    }
}
