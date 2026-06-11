<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

/**
 * User avatar area in the header.
 *
 * Holds user data and the dropdown links rendered when authenticated.
 * When `$authenticated` is false, `$guestLinks` are rendered instead (login/register buttons).
 */
class AvatarContext extends AbstractContextItem
{
    /** @var MenuItemContext[] */
    protected array $userLinks  = [];

    /** @var MenuItemContext[] */
    protected array $guestLinks = [];

    public function __construct(
        string           $id            = 'user_avatar',
        protected bool   $authenticated = false,
        protected string $name          = '',
        protected string $email         = '',
        protected string $initials      = '',
        protected string $avatarUrl     = '',
        int              $priority      = 100,
        bool             $visible       = true,
    ) {
        parent::__construct($id, $priority, $visible);
    }

    public function addUserLink(MenuItemContext $link): static
    {
        $this->userLinks[$link->getId()] = $link;
        return $this;
    }

    public function addGuestLink(MenuItemContext $link): static
    {
        $this->guestLinks[$link->getId()] = $link;
        return $this;
    }

    public function resolve(): array
    {
        $sortLinks = function (array $links): array {
            usort($links, fn(MenuItemContext $a, MenuItemContext $b) => $a->getPriority() <=> $b->getPriority());
            return array_map(fn(MenuItemContext $l) => $l->resolve(), $links);
        };

        return array_merge($this->baseResolve(), [
            'authenticated' => $this->authenticated,
            'name'          => $this->name,
            'email'         => $this->email,
            'initials'      => $this->initials,
            'avatar_url'    => $this->avatarUrl,
            'user_links'    => $sortLinks(array_values($this->userLinks)),
            'guest_links'   => $sortLinks(array_values($this->guestLinks)),
        ]);
    }
}
