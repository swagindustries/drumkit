<?php
declare(strict_types=1);
namespace SwagIndustries\MercureRouter\Test\Functional\Pusher;

final class Event
{
    public function __construct(private string|array $content, private string $topic)
    {
    }

    public function topic(): string
    {
        return $this->topic;
    }

    public function content(): string|array
    {
        return $this->content;
    }
}
