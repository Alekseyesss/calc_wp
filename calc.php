<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

add_shortcode('entry_point_calc', 'create_div');

function create_div()
{
  $ajaxurl = esc_url(admin_url('admin-ajax.php'));
  return "<div data-ajaxurl='${ajaxurl}' class='wrapper_insert_table'></div>";
}


function af_filter()
{
  delete_transient('af_main_data');

  function get_data()
  {
    $response = wp_safe_remote_get('https://my.accentforex.com/ratingJSON');
    $response = wp_remote_retrieve_body($response);

    if ($response == '') {
      wp_die();
    }

    $data = json_decode($response, true);
    foreach ($data as $key => $row) {
      // check for keys in each response array
      if (
        !array_key_exists('id', $row)
        ||
        !array_key_exists('trader', $row)
        ||
        !array_key_exists('min', $row)
        ||
        !array_key_exists('days', $row)
        ||
        !array_key_exists('rating', $row)
        ||
        !array_key_exists('profit', $row)
      ) {
        wp_die();
      }
    }
    return $data;
  };

  $data = get_transient('af_main_data');
  if (!is_array($data)) {
    $data = get_data();
    set_transient('af_main_data', $data, 3600);
  }

  foreach ($data as $key => $row) {
    if (
      preg_replace("/[^0-9]/", '', $row['min']) > $_POST['min']
      ||
      preg_replace("/[^0-9\.]/", '', $row['profit']) < $_POST['profit']
      ||
      preg_replace("/[^0-9]/", '', $row['days']) < $_POST['days']
    ) {
      unset($data[$key]);
      continue;
    }

    $af_rating[$key]  = $row['rating'];
  }
  array_multisort($af_rating, SORT_DESC, $data);

  $total = ceil(count($data) / 10);
  $current_page = 1;
  if ($_POST['current_page']) {
    $current_page = $_POST['current_page'];
  }

  ob_start();
?>
  <div class="my_table_wrapper">
    <table class="my_table">
      <tr class="table-header">
        <th>
          <div class="table-header-th">Trader</div>
        </th>
        <th>
          <div class="table-header-th">Current profitability</div>
        </th>
        <th>
          <div class="table-header-th">Activity days</div>
        </th>
        <th>
          <div class="table-header-th">The minimum investment</div>
        </th>
        <th>
          <div class="table-header-th">Total profit</div>
        </th>
        <th>
          <div class="table-header-th">Add</div>
        </th>
      </tr>
      <?php
      $part_array = array_slice($data, ($current_page - 1) * 10, 10);
      foreach ($part_array as $key => $value) :
        $pieces_trader = explode(";", $value['trader']);

        if (substr($value['profit'], 0, 1) == '-') {
          $negative_profit = 'af-negative-profit';
        } else {
          $negative_profit = '';
        };

        if (is_user_logged_in()) {
          $link_invest = 'https://my.accentforex.com/pammDetailInfo/' . $value['id'] . '?setLng=en';
        } else {
          $link_invest = 'https://my.accentforex.com/login';
        }

        $total_profit = preg_replace("/[^0-9\.]/", '', $value['profit']) * $_POST['min'] * $_POST['period'] / 100;
      ?>
        <tr>
          <td class="af-trider">
            <a href="<?php echo 'https://my.accentforex.com/pammDetailInfo/' . $value['id'] . '?setLng=en' ?>">
              <?php
              echo "<img src='$pieces_trader[0]' alt='trader-icon'>"
              ?>
              <br>
              <span class="tradet-name"><?php echo $pieces_trader[1] ?></span>
            </a>
            <div>
              <svg viewBox="0 0 1000 200" class='rating' width="56" height="14">
                <defs>

                  <polygon id="star" points="100,0 131,66 200,76 150,128 162,200 100,166 38,200 50,128 0,76 69,66 " />

                  <clipPath id="stars">
                    <use xlink:href="#star" />
                    <use xlink:href="#star" x="20%" />
                    <use xlink:href="#star" x="40%" />
                    <use xlink:href="#star" x="60%" />
                    <use xlink:href="#star" x="80%" />
                  </clipPath>

                </defs>

                <rect class='rating__background' clip-path="url(#stars)"></rect>

                <!-- Change the width of this rect to change the rating -->
                <rect width='<?php echo $value["rating"] * 100 / 5 . "%" ?>' class='rating__value' clip-path='url(#stars)'></rect>
              </svg>
            </div>
          </td>
          <td class="af-profit <?php echo $negative_profit ?>"><?php echo $value['profit'] ?></td>
          <td><?php echo $value['days'] ?></td>
          <td><?php echo $value['min'] ?></td>
          <td><?php echo "$ " .  number_format($total_profit, 2, '.', ' ') ?></td>
          <td><?php echo '<a class="button-invest" href="' . $link_invest . '">Invest</a>' ?>
          </td>
        </tr>
      <?php
      endforeach;
      ?>
    </table>

  <?php

  $args = [
    'format' => '?link=%#%',
    'current' => $current_page,
    'total'   => $total,
  ];


  echo paginate_links($args);

  echo '</div>';

  echo ob_get_clean();
  wp_die();
}
