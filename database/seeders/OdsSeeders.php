<?php
namespace Database\Seeders;

use App\Models\Ods;
use Illuminate\Database\Seeder;

class OdsSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

     public function run()
     {
         Ods::insert([
             [
                 'code' => 'ODS01',
                 'name' => 'Fin de la pobreza',
                 'description' => 'Erradicar la pobreza en todas sus formas y en todo el mundo.',
                 'color' => '#E31837', // Rojo (para el ODS 1)
             ],
             [
                 'code' => 'ODS02',
                 'name' => 'Hambre cero',
                 'description' => 'Erradicar el hambre, lograr la seguridad alimentaria, mejorar la nutrición y promover la agricultura sostenible.',
                 'color' => '#F5B300', // Amarillo (para el ODS 2)
             ],
             [
                 'code' => 'ODS03',
                 'name' => 'Salud y bienestar',
                 'description' => 'Garantizar una vida sana y promover el bienestar para todos en todas las edades.',
                 'color' => '#A7D08B', // Verde (para el ODS 3)
             ],
             [
                 'code' => 'ODS04',
                 'name' => 'Educación de calidad',
                 'description' => 'Garantizar una educación inclusiva, equitativa y de calidad, y promover oportunidades de aprendizaje durante toda la vida para todos.',
                 'color' => '#4C9F38', // Verde (para el ODS 4)
             ],
             [
                 'code' => 'ODS05',
                 'name' => 'Igualdad de género',
                 'description' => 'Lograr la igualdad de género y empoderar a todas las mujeres y las niñas.',
                 'color' => '#F56B00', // Naranja (para el ODS 5)
             ],
             [
                 'code' => 'ODS06',
                 'name' => 'Agua limpia y saneamiento',
                 'description' => 'Garantizar la disponibilidad y la gestión sostenible del agua y el saneamiento para todos.',
                 'color' => '#009C95', // Azul (para el ODS 6)
             ],
             [
                 'code' => 'ODS07',
                 'name' => 'Energía asequible y no contaminante',
                 'description' => 'Garantizar el acceso de todos a servicios de energía asequible, fiable, sostenible y moderno.',
                 'color' => '#F7A800', // Amarillo (para el ODS 7)
             ],
             [
                 'code' => 'ODS08',
                 'name' => 'Trabajo decente y crecimiento económico',
                 'description' => 'Promover el crecimiento económico sostenido, inclusivo y sostenible, el empleo pleno y productivo y el trabajo decente para todos.',
                 'color' => '#5A2E8C', // Púrpura (para el ODS 8)
             ],
             [
                 'code' => 'ODS09',
                 'name' => 'Industria, innovación e infraestructura',
                 'description' => 'Construir infraestructuras resilientes, promover la industrialización inclusiva y sostenible y fomentar la innovación.',
                 'color' => '#F1C40F', // Amarillo (para el ODS 9)
             ],
             [
                 'code' => 'ODS10',
                 'name' => 'Reducción de las desigualdades',
                 'description' => 'Reducir la desigualdad en y entre los países.',
                 'color' => '#DC6B9F', // Rosa (para el ODS 10)
             ],
             [
                 'code' => 'ODS11',
                 'name' => 'Ciudades y comunidades sostenibles',
                 'description' => 'Lograr que las ciudades y los asentamientos humanos sean inclusivos, seguros, resilientes y sostenibles.',
                 'color' => '#33B5E5', // Azul (para el ODS 11)
             ],
             [
                 'code' => 'ODS12',
                 'name' => 'Producción y consumo responsables',
                 'description' => 'Garantizar modalidades de consumo y producción sostenibles.',
                 'color' => '#F2C37B', // Amarillo (para el ODS 12)
             ],
             [
                 'code' => 'ODS13',
                 'name' => 'Acción por el clima',
                 'description' => 'Mejorar la educación, la concienciación y la capacidad humana e institucional sobre la mitigación del cambio climático, la adaptación a él, la reducción de sus efectos y la alerta temprana.',
                 'color' => '#3E9A63', // Verde (para el ODS 13)
             ],
             [
                 'code' => 'ODS14',
                 'name' => 'Vida submarina',
                 'description' => 'Conservar y utilizar de manera sostenible los océanos, los mares y los recursos marinos para el desarrollo sostenible.',
                 'color' => '#006F9B', // Azul oscuro (para el ODS 14)
             ],
             [
                 'code' => 'ODS15',
                 'name' => 'Vida de ecosistemas terrestres',
                 'description' => 'Gestionar de manera sostenible los bosques, combatir la desertificación, frenar la degradación de la tierra y frenar la pérdida de biodiversidad.',
                 'color' => '#8B7D4B', // Marrón (para el ODS 15)
             ],
             [
                 'code' => 'ODS16',
                 'name' => 'Paz, justicia e instituciones sólidas',
                 'description' => 'Promover sociedades pacíficas e inclusivas para el desarrollo sostenible, facilitar el acceso a la justicia para todos y construir instituciones eficaces, responsables e inclusivas en todos los niveles.',
                 'color' => '#2A4697', // Azul (para el ODS 16)
             ],
             [
                 'code' => 'ODS17',
                 'name' => 'Alianzas para lograr los objetivos',
                 'description' => 'Fortalecer los medios de ejecución y revitalizar la alianza mundial para el desarrollo sostenible.',
                 'color' => '#B6D73D', // Verde claro (para el ODS 17)
             ],
         ]);
     }
}
