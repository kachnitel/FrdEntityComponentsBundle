<?php

namespace Frd\EntityComponentsBundle\Interface;

use Doctrine\Common\Collections\Collection;

interface CommentableInterface
{
    /**
     * @return Collection<int, CommentInterface>
     */
    public function getComments(): Collection;

    public function addComment(CommentInterface $comment): self;

    public function removeComment(CommentInterface $comment): self;
}
