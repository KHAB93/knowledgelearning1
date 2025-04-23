<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OrderRepository;
use Dompdf\Options;
use Dompdf\Dompdf;

final class BillController extends AbstractController
{
    #[Route('/admin/order/{id}/bill', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException("Commande non trouvée.");
        }

        $pdfOptions = new Options();

        $pdfOptions->set('defaultFont','Arial');

        $domPdf= new Dompdf($pdfOptions);

        $html = $this->renderView('bill/index.html.twig', [
            'order'=>$order
        ]);

        $domPdf->loadHtml($html);

        $domPdf->render();

        $domPdf->stream( "STUBBORN-".$order->getId().".pdf",[
            'Attachment' => false 
        ]);

        exit;

        return new Response(
            content: '', 
            status: 200, 
            headers: ['Content-Type' => 'application/pdf']
        );

        
    }
}
