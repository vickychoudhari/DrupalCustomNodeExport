<?php

namespace Drupal\custom_node_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;


/**
 * Configure Custom_Node_export settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_node_export_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_node_export.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['year'] = [
      '#type' => 'select',
      '#title' => t('Year'),
      '#options' => $this->getYearforNode(),
      '#empty_option' => '- All -',
    ];
    $form['node_type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => $this->getNodeType(),
      '#empty_option' => '- All -',
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Export'),
        '#submit' => ['::export_data'],
      ],
    ];
    return $form;
  }
  function export_data(array &$form, FormStateInterface $form_state) {
    $getyear = $form_state->getValue('year');
    $getType = $form_state->getValue('node_type');

    $database = \Drupal::database();
    $query = $database->select('node_field_data','nfd');
    $query->join('node__field_year', 'nfy', 'nfd.nid = nfy.entity_id');
    $query->fields('nfd',['nid','title', 'type']);
    $query->fields('nfy',['field_year_value']);
    $query->condition('nfd.type', $getType);
    $query->condition('nfy.field_year_value', $getyear);
    $result = $query->execute();

// Fetch the result records.
$rows = $result->fetchAll();
 // Generate CSV data.
 $csv_data = $this->generateCsvData($rows);

 // Get the current date and time.
$current_date = \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y-m-d');

 // Set the headers for a CSV download.

 $headers = [
   'Content-Type' => 'text/csv',
   'Content-Disposition' => 'attachment; filename=NodeData-' . $current_date . '-' . $getType . '.csv',
 ];

 // Send the CSV data as the response.
 $response = new \Symfony\Component\HttpFoundation\Response($csv_data, 200, $headers);
 $response->send();

 exit();
}


/**
* Generate CSV data.
*/
  public function generateCsvData(array $rows) {
    // dump($rows);die;
 $csv_data = "Nid,Title,Type,Year\n";

 foreach ($rows as $nodes) {
  $csv_data .= $nodes->nid. ',' . $nodes->title. ',' . $nodes->type. ',' . $nodes->field_year_value . "\n";
 }

 return $csv_data;





  }

  public function getYearforNode() {
    $database = \Drupal::database();
    $query = $database->select('node_field_data', 'nfd');
    $query->join('node__field_year', 'nfy', 'nfd.nid = nfy.entity_id');
    $query->fields('nfd', ['nid']);
    $query->fields('nfy', ['field_year_value']);
    $result = $query->execute()->fetchAll();

    // Initialize an array to store the year values.
    $years = [];

    // Iterate over the results and extract the year values.
    foreach ($result as $results) {
        // Get the 'field_year_value' from the result.
        $year = $results->field_year_value;

        // Add the year value to the array with the label.
        $years[$year] = $year;
    }

    // Return the array of year values with labels.
    return $years;
}

public function getNodeType() {
  $content_types = NodeType::loadMultiple();
  // dump($content_types);die;
  $options = [];
  foreach ($content_types as $key => $content_type) {
      $type_name = $content_type->label();
      // Do something with the content type name, like print it.
      $options[$key] = $type_name;
  }
  return $options;

}
}