<?php

namespace NaidusvoeBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use NaidusvoeBundle\Entity\Notification;
use NaidusvoeBundle\Entity\User;
use Swift_Mailer;
use Symfony\Component\DependencyInjection\Container;

class Notifier
{
    /** @var EntityManager $em */
    private $em;
    /** @var QueryBuilder */
    private $qb;
    /** @var Swift_Mailer */
    private $mailer;
    private $templating;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, Container $container) {
        $this->em = $entityManager;
        $this->qb = $entityManager->createQueryBuilder();
        $this->mailer = $container->get('mailer');
        $this->templating = $container->get('templating');
    }

    public function addNotification($users, $notificationType, $notificationDetails) {
        foreach($users as $user)
        {
            $initiator = null;
            $notification = new Notification($notificationType, $user);
            if($notificationType===Notification::SIMPLE_NOTIFICATION)
            {
                $notification->setContent($notificationDetails['content']);
                $initiator = "Naidusvoe";
            }
            if($notificationType===Notification::CONVERSATION_NOTIFICATION)
            {
                $notification->setConversationId($notificationDetails['conversationId']);
                $notification->setMessageId($notificationDetails['messageId']);
                $notification->setContent("You got private message from user");
                $userInitiatorId = $notificationDetails['userInitiatorId'];
                $initiator = $this->em->find('NaidusvoeBundle::User', $userInitiatorId)->getUsername();
                $notification->setInitiatorId($userInitiatorId);
            }
            $this->em->persist($notification);
            $this->em->flush();
            if($user->getSettings()->getNotificationsEmail())
            {
                $message = \Swift_Message::newInstance()
                    ->setSubject('You have some new notification')
                    ->setFrom('send@example.com')
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->templating->render('@Naidusvoe/mail.html.twig', [
                            'content'=>$notification->getContent(),
                            'Initiator'=>$initiator
                        ]),
                        'text/html'
                    );
                $this->mailer->send($message);
            }
        }
    }

    public function RemoveNotifications($user, $notificationType, $notificationDetails){
        if($notificationType===Notification::CONVERSATION_NOTIFICATION)
        {
            $conversationId = $notificationDetails['conversation_id'];
            $messageId = $notificationDetails['message_id'];
            $this->qb->delete('Notification', 'n')
                ->where('n.user_id=:uid')
                ->setParameter('uid',$user->getId())
                ->andWhere('n.conversation_id=:convid')
                ->setParameter('convid', $conversationId)
                ->andWhere('n.message_id=:message_id')
                ->setParameter('message_id',$messageId)
                ->getQuery();
        }
    }
}