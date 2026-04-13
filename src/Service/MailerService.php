<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{
    private MailerInterface $mailer;
    private string $fromEmail;
    private string $fromName;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $this->fromEmail = 'abdelrahmanhamoudi8@gmail.com';
        $this->fromName = 'RE7LA';
    }

    public function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    public function sendVerificationCode(string $to, string $userName, string $code): void
    {
        $html = $this->buildVerificationHtml($userName, $code);
        $this->sendEmail($to, '🔐 RE7LA - Vérification de votre email', $html);
    }

    public function sendResetPasswordCode(string $to, string $userName, string $code): void
    {
        $html = $this->buildResetPasswordHtml($userName, $code);
        $this->sendEmail($to, '🔑 RE7LA - Réinitialisation du mot de passe', $html);
    }

    private function buildVerificationHtml(string $userName, string $code): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <div style="background: linear-gradient(135deg, #1ABC9C 0%, #16A085 100%); padding: 30px; text-align: center;">
                    <h1 style="color: white; margin: 0; font-size: 28px;">RE7LA</h1>
                    <p style="color: white; margin: 8px 0 0; opacity: 0.9;">Vérification de votre email</p>
                </div>
                <div style="padding: 40px 30px;">
                    <h2 style="color: #2C3E50; margin-top: 0;">Bonjour ' . htmlspecialchars($userName) . ' 👋</h2>
                    <p style="color: #555; font-size: 15px; line-height: 1.6;">
                        Merci de vous être inscrit sur RE7LA ! Pour finaliser votre inscription, veuillez utiliser le code de vérification ci-dessous :
                    </p>
                    <div style="background: #f8f9fa; border: 2px dashed #1ABC9C; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                        <p style="margin: 0 0 10px; color: #7f8c8d; font-size: 13px;">VOTRE CODE DE VÉRIFICATION</p>
                        <div style="font-size: 36px; font-weight: bold; color: #1ABC9C; letter-spacing: 8px;">' . $code . '</div>
                    </div>
                    <p style="color: #555; font-size: 14px;">
                        Ce code est valable pendant <strong>15 minutes</strong>. Si vous n\'avez pas créé de compte sur RE7LA, ignorez cet email.
                    </p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; text-align: center; color: #95a5a6; font-size: 12px;">
                    © ' . date('Y') . ' RE7LA. Tous droits réservés.
                </div>
            </div>
        </body>
        </html>';
    }

    private function buildResetPasswordHtml(string $userName, string $code): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <div style="background: linear-gradient(135deg, #F39C12 0%, #e67e22 100%); padding: 30px; text-align: center;">
                    <h1 style="color: white; margin: 0; font-size: 28px;">RE7LA</h1>
                    <p style="color: white; margin: 8px 0 0; opacity: 0.9;">Réinitialisation du mot de passe</p>
                </div>
                <div style="padding: 40px 30px;">
                    <h2 style="color: #2C3E50; margin-top: 0;">Bonjour ' . htmlspecialchars($userName) . ' 👋</h2>
                    <p style="color: #555; font-size: 15px; line-height: 1.6;">
                        Vous avez demandé à réinitialiser votre mot de passe. Utilisez le code ci-dessous pour définir un nouveau mot de passe :
                    </p>
                    <div style="background: #fff9ec; border: 2px dashed #F39C12; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                        <p style="margin: 0 0 10px; color: #7f8c8d; font-size: 13px;">VOTRE CODE DE RÉINITIALISATION</p>
                        <div style="font-size: 36px; font-weight: bold; color: #F39C12; letter-spacing: 8px;">' . $code . '</div>
                    </div>
                    <p style="color: #555; font-size: 14px;">
                        Ce code est valable pendant <strong>15 minutes</strong>. Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email et votre mot de passe restera inchangé.
                    </p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; text-align: center; color: #95a5a6; font-size: 12px;">
                    © ' . date('Y') . ' RE7LA. Tous droits réservés.
                </div>
            </div>
        </body>
        </html>';
    }
}