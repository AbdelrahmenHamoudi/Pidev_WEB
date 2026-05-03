<?php

namespace App\Service;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ActiviteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ActiviteRepository     $repository,
        private SluggerInterface       $slugger,
        private string                 $uploadDir,   // injecte depuis services.yaml
    ) {}

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findById(int $id): ?Activite
    {
        return $this->repository->find($id);
    }

    public function findDisponibles(): array
    {
        return $this->repository->findDisponibles();
    }

    public function findByFilters(?string $type, ?float $prixMax): array
    {
        return $this->repository->findByFilters($type, $prixMax);
    }

    public function create(Activite $activite, ?UploadedFile $imageFile): Activite
    {
        if ($imageFile) {
            $activite->setImage($this->handleImageUpload($imageFile));
        }

        $this->em->persist($activite);
        $this->em->flush();

        return $activite;
    }

    public function update(Activite $activite, ?UploadedFile $imageFile): Activite
    {
        if ($imageFile) {
            // Supprimer l'ancienne image si elle existe
            $this->deleteImage($activite->getImage());
            $activite->setImage($this->handleImageUpload($imageFile));
        }

        $this->em->flush();
        return $activite;
    }

    public function delete(Activite $activite): void
    {
        $this->deleteImage($activite->getImage());
        $this->em->remove($activite);
        $this->em->flush();
    }

    // ── Metier ────────────────────────────────────────────────────────────

    /**
     * Met a jour le statut automatiquement selon les places restantes
     */
    public function updateStatut(Activite $activite): void
    {
        $placesTotal = $activite->getPlannings()
            ->filter(fn($p) => $p->getEtat() === 'Disponible')
            ->reduce(fn($carry, $p) => $carry + $p->getNbPlacesRestantes(), 0);

        $activite->setStatut($placesTotal > 0 ? 'Disponible' : 'Complet');
        $this->em->flush();
    }

    // ── Upload image ──────────────────────────────────────────────────────

    private function handleImageUpload(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName     = $this->slugger->slug($originalName);
        $fileName     = $safeName . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadDir, $fileName);

        return $fileName;
    }

    private function deleteImage(?string $filename): void
    {
        if ($filename && file_exists($this->uploadDir . '/' . $filename)) {
            unlink($this->uploadDir . '/' . $filename);
        }
    }
}