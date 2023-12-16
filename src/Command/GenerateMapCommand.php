<?php

namespace App\Command;

use App\Entity\Fields;
use App\Entity\Map;
use App\Entity\Objects;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateMapCommand extends Command
{
    protected static $defaultName = 'generate:map';
    protected static $defaultDescription = 'Generates a new Map for the game';
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Fields[]
     */
    private $fields;
    /**
     * @var Objects[]
     */
    private $objects;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        define('PERLIN_YWRAPB', 4);
        define('PERLIN_YWRAP', 1 << PERLIN_YWRAPB);
        define('PERLIN_ZWRAPB', 8);
        define('PERLIN_ZWRAP', 1 << PERLIN_ZWRAPB);
        define('PERLIN_SIZE', 4095);
        define('field_zoom', 0.05);
        define('object_zoom', 0.1);

        
        $GLOBALS["perlin_octaves"] = 4;
        $GLOBALS["perlin_amp_falloff"] = 0.5;
        
        $GLOBALS["perlin"] = null;
        
        //$this->addArgument('width', InputArgument::OPTIONAL, 'Width of the map', 150);
        //$this->addArgument('height', InputArgument::OPTIONAL, 'Height of the map', 150);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $map_width = 100;//$input->getArgument('width');
        $map_height = $map_width;//$input->getArgument('height');
        $io->success('Generating new map with size '.$map_width.'x'.$map_height.'!');
        
        $fieldsTable = $this->entityManager->getRepository(Fields::class);
        $fields = $fieldsTable->findAll();
        
        $grassField = null;
        
        foreach($fields as $value) {
            $this->fields[] = $value;
            if($value->getName() == "grass") $grassField = $value;
        }
        
        $objectsTable = $this->entityManager->getRepository(Objects::class);
        $objects = $objectsTable->findAll();
        
        foreach($objects as $value) {
            $this->objects[] = $value;
        }
                
        $pixels = [];
        
        
        $yOffset = 0;
        
        
        $io->success('Generating fields!');
        for ($y = 0; $y < $map_height; $y++) {
            $xOffset = 0;
            for ($x = 0; $x < $map_width; $x++) {
                $index = ($x + $y * $map_width) * 3;
                $r = intval($this->noise($xOffset, $yOffset) * 255);
                
                foreach ($this->fields as $field) {
                    if ($r >= $field->getStarts() && $r <= $field->getEnds()) {
                        $pixels[$index] = $field->getPixelsR();
                        $pixels[$index + 1] = $field->getPixelsG();
                        $pixels[$index + 2] = $field->getPixelsB();
                    }
                }

                $xOffset += field_zoom;
            }
            $yOffset += field_zoom;
        }
        
        $io->success('Generating objects!');
        $yOffset = 0;
        for ($y = 0; $y < $map_height; $y++) {
            $xOffset = 0;
            for ($x = 0; $x < $map_width; $x++) {
                $index = ($x + $y * $map_width) * 3;
                $r = intval($this->noise($xOffset, $yOffset) * 255);
                
                if ($pixels[$index] == $grassField->getPixelsR() && $pixels[$index + 1] == $grassField->getPixelsG() && $pixels[$index + 2] == $grassField->getPixelsB()) {
                    foreach ($this->objects as $object) {
                        if ($r >= $object->getStarts() && $r <= $object->getEnds()) {
                            $pixels[$index] = $object->getPixelsR();
                            $pixels[$index + 1] = $object->getPixelsG();
                            $pixels[$index + 2] = $object->getPixelsB();
                        }
                    }
                }
                
                $xOffset += object_zoom/4.0;
            }
            $yOffset += object_zoom/4.0;
        }
        
        
        
        $io->success('Building map!');
        for($i = 0; $i < sizeof($pixels)/3; $i++)
        {
            $r = $pixels[$i*3+0];
            $g = $pixels[$i*3+1];
            $b = $pixels[$i*3+2];
            
            $x = $i%$map_width;
            $y = $i/$map_width;

            
            foreach ($this->fields as $fields) {
                if ($r == $fields->getPixelsR() && $g == $fields->getPixelsG() && $b == $fields->getPixelsB()) {
                    $field[$x][$y] = $fields->getId();
                }
            }
            
            $object[$x][$y] = 0;
            foreach ($this->objects as $f_object) {
                if ($r == $f_object->getPixelsR() && $g == $f_object->getPixelsG() && $b == $f_object->getPixelsB()) {
                    $field[$x][$y] = $grassField->getId();
                    $object[$x][$y] = $f_object->getId();
                }
            }
        }
        
        $io->success('Saving map!');
        foreach ($fields as $x=>$field) {
            foreach($field as $y=>$value) {
                
                $mapEntry = new Map();
                $mapEntry->setX($x);
                $mapEntry->setY($y);
                $mapEntry->setValue($value);
                $mapEntry->setType("field");
                $this->entityManager->persist($mapEntry);
            }
        }
        
        foreach ($objects as $x=>$field) {
            foreach($field as $y=>$value) {
                
                $mapEntry = new Map();
                $mapEntry->setX($x);
                $mapEntry->setY($y);
                $mapEntry->setValue($value);
                $mapEntry->setType("object");
                $this->entityManager->persist($mapEntry);
            }
        }
        $this->entityManager->flush();
        

        $io->success('Map successfully generated!');

        return Command::SUCCESS;
    }
    
    
    function scaled_cosine($i) : float {
        return 0.5 * (1 - cos($i * pi()));
    }
    
    function noise($x, $y = 0, $z = 0) : float {
        if ($GLOBALS["perlin"] == null) {
            $GLOBALS["perlin"] = array();
            for ($i = 0; $i < PERLIN_SIZE + 1; $i++) {
                $GLOBALS["perlin"][$i] = mt_rand() / mt_getrandmax();
            }
        }
        
        if ($x < 0) {
            $x = -$x;
        }
        
        if ($y < 0) {
            $y = -$y;
        }
        
        if ($z < 0) {
            $z = -$z;
        }
        
        $xi = floor($x);
        $yi = floor($y);
        $zi = floor($z);
        $xf = $x - $xi;
        $yf = $y - $yi;
        $zf = $z - $zi;
        $r = 0;
        $ampl = 0.5;
        
        for ($o = 0; $o < $GLOBALS["perlin_octaves"]; $o++) {
            $of = $xi + ($yi << PERLIN_YWRAPB) + ($zi << PERLIN_ZWRAPB);
            $rxf = $this->scaled_cosine($xf);
            $ryf = $this->scaled_cosine($yf);
            $n1 = $GLOBALS["perlin"][$of & PERLIN_SIZE];
            $n1 += $rxf * ($GLOBALS["perlin"][($of + 1) & PERLIN_SIZE] - $n1);
            $n2 = $GLOBALS["perlin"][($of + PERLIN_YWRAP) & PERLIN_SIZE];
            $n2 += $rxf * ($GLOBALS["perlin"][($of + PERLIN_YWRAP + 1) & PERLIN_SIZE] - $n2);
            $n1 += $ryf * ($n2 - $n1);
            $of += PERLIN_ZWRAP;
            $n2 = $GLOBALS["perlin"][$of & PERLIN_SIZE];
            $n2 += $rxf * ($GLOBALS["perlin"][($of + 1) & PERLIN_SIZE] - $n2);
            $n3 = $GLOBALS["perlin"][($of + PERLIN_YWRAP) & PERLIN_SIZE];
            $n3 += $rxf * ($GLOBALS["perlin"][($of + PERLIN_YWRAP + 1) & PERLIN_SIZE] - $n3);
            $n2 += $ryf * ($n3 - $n2);
            $n1 += $this->scaled_cosine($zf) * ($n2 - $n1);
            $r += $n1 * $ampl;
            $ampl *= $GLOBALS["perlin_amp_falloff"];
            $xi <<= 1;
            $xf *= 2;
            $yi <<= 1;
            $yf *= 2;
            $zi <<= 1;
            $zf *= 2;
            if ($xf >= 1) {
                $xi++;
                $xf--;
            }
            if ($yf >= 1) {
                $yi++;
                $yf--;
            }
            if ($zf >= 1) {
                $zi++;
                $zf--;
            }
        }
        
        return $r;
    }
}
