<?php

class emuTheme extends emuApp
{
    function config()
    {
        $this->emuAppID = 'emuTheme';
        $this->menuName = 'Dry Master';
        $this->dbPrefix = 'emu_dry_';
        // $this->forceInstall = true;
    }

    function init()
    {
        $this->loadManager('theme');
        $this->loadManager('bags');
        $this->loadManager('user-admin');
    }

    function loadCoreStyles()
    {
        if( !is_admin() )
        {
            wp_enqueue_style('bootstrap', $this->sThemeURL.'/css/bootstrap.css');
            wp_enqueue_style('bootstrap-theme', $this->sThemeURL.'/css/bootstrap-theme.min.css', array('bootstrap'));
            wp_enqueue_style('styles', $this->sThemeURL.'/style.css', array('bootstrap-theme'));
        }
    }

    // Taken from emu-framework
    function getMessage( $key, $wrapper_start = '', $wrapper_end = '', $type = 'notice' )
    {
        $this->loadMessages();

        if( ! isset( $this->messages[ $key ][ $type ] ) ) return '';

        $message_output = '';

        foreach( $this->messages[ $key ][ $type ] as $message )
        {
            switch( $type )
            {
                case 'error':
                    $message_output .= '<div class="alert alert-danger">'; break;
                case 'notice':
                    $message_output .= '<div class="alert alert-info">'; break;
                case 'success':
                    $message_output .= '<div class="alert alert-success">'; break;
                default:
                    $message_output .= '<div>'; break;
            }
            $message_output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'.$wrapper_start.$message.$wrapper_end.'</div>';
        }

        unset( $this->messages[ $key ][ $type ] );

        $this->saveMessages();

        return $message_output;

    }

    public function getPhases($plots, $threshold = 2, $excluded = array())
    {
      require_once $this->pluginPath.'/lib/PHPExcel/Classes/PHPExcel.php';
      
      $x_vals = array();
      $y_vals = array();

      $stored_steyx = array();

      // Our calculated values
      $slope = '';
      $intercept = '';
      $steyx = '';
      $diff_with_previous = '';
      $poly = 0;

      // To record our different phases
      $phases = array();

      // Start with the first phase...
      $current_phase = 0;

      // Loop through the points in each sequence
      foreach($plots as $plot)
      {
        $x = (float) $plot[0];
        $y = (float) $plot[1];

        $exclude_current_points = false;

        // Do we need to exclude these points
        foreach($excluded as $exclude)
        {
          $exclude_points = explode(',', $exclude);
          
          if( $x == (float) $exclude_points[0] && $y == (float) $exclude_points[1] )
            $exclude_current_points = true;
        }

        if($exclude_current_points)
        {
          // We'll still add the points to the members list...
          $phases[$current_phase]["members"][] = (object) array(
              'x' => $x, 
              'y' => $y, 
              'steyx' => '-', 
              'steyxDiff' => '-',
              'slope' => '-',
              'trend' => '-',
              'regression' => '-',
              'intercept' => '-',
              'excluded' => true
          );

          // but nothing else...
          continue;
        }

        $x_vals[] = $x;
        $y_vals[] = $y;
      
        if( count($x_vals) >= 2 )
        {
          $slope = PHPExcel_Calculation_Statistical::SLOPE($y_vals, $x_vals);
          $intercept = PHPExcel_Calculation_Statistical::INTERCEPT($y_vals, $x_vals);
        }

        // And we can only do the std error of the regression calculation when
        // we have at least three points
        if( count($x_vals) >= 3 )
        {
          $stored_steyx[] = $steyx = PHPExcel_Calculation_Statistical::STEYX($y_vals, $x_vals);
        }
        else
        {
          $steyx = 0;
          $diff_with_previous = 0;
        }
        
        // Do we have a new phase?

        // Well, we can only check for a new phase if we have at least two steyx values to compare...
        if(count($stored_steyx) >= 2)
        {
          // Get the current stored array position
          $current_array_position = count($stored_steyx) - 1; // -1 because array indexes start at 0

          // Compare the current steyx with the previous...
          $diff_with_previous = $stored_steyx[$current_array_position] / $stored_steyx[$current_array_position - 1];
          
          // ... and if it is more than our threshold then ...
          if( $diff_with_previous > (float) $threshold ) 
          {
            // ... we're (probably) into a new phase.
            $current_phase++;
            
            // Start with a new set of points and steyx vals (add the current set of points and steyx values as the initial 
            // values for the new phase)
            $x_vals = array(array_pop($x_vals));
            $y_vals = array(array_pop($y_vals));
            $stored_steyx = array(array_pop($stored_steyx));

            // Because we can't get a slope or intercept from one set of points
            // we'll use the last calculated slope and intercept values (which
            // grouped the first new phase points into the set of last phase points)
            // i.e. we won't do this here:
            // $slope = ''; $intercept = '';
          }
        
        }


        // ... we can only do the regression when have at least 2 points  
        
        $sxxlope = PHPExcel_Calculation_Statistical::SLOPE($y_vals, $x_vals);

        if(count($x_vals) == 1)
        {
          // This is a special case. If we have a previous phase then the only thing we can do is
          // do a linear regression with the last point of the previous phase, making the assumption that that
          // point is the most related to the new phase as any other point.
          if($current_phase > 0)
          {
            $previous_phase = $phases[$current_phase - 1];

            $special_x_vals = array_merge($x_vals, array($previous_phase["members"][(count($previous_phase["members"]))-1]->x));
            $special_y_vals = array_merge($y_vals, array($previous_phase["members"][(count($previous_phase["members"]))-1]->y));
            $regression = $this->getBestFitRegression($special_x_vals, $special_y_vals);
          }
          else
          {
            $regression = '';
          }
        }
        else
        {
          $regression = $this->getBestFitRegression($x_vals, $y_vals);
        }

        // Update the current phase values
        $phases[$current_phase]["members"][] = (object) array(
            'x' => $x, 
            'y' => $y, 
            'steyx' => $steyx, 
            'steyxDiff' => $diff_with_previous,
            'slope' => $slope,
            'intercept' => $intercept,
            'regression' => $regression,
            'excluded' => false
        );

        $phases[$current_phase]["slope"] = $slope; // Replace the last calculated slope
        $phases[$current_phase]["intercept"] = $intercept; // Replace the last calculated intercept
        $phases[$current_phase]["number"] = $current_phase;
        $phases[$current_phase]["regression"] = $regression;
        $phases[$current_phase]["x_vals"] = implode(',', $x_vals);
        $phases[$current_phase]["y_vals"] = implode(',', $y_vals);

      }
    
      return $phases;
    }    

    public function getBestFitRegression($x_vals, $y_vals)
    {
        $x_count = count($x_vals);

        $regressions = array();

        switch(true)
        {
          case ($x_count > 4):
          
            // $trend = trendClass::TREND_POLYNOMIAL_4;
            // $type = "poly_4";
            // $calc = trendClass::calculate($trend, $y_vals, $x_vals);

            // $regressions[] = (object) array( 'type' => $type, 'equation' => $calc->getEquation(), 'slope' => $calc->getSlope(), 'intercept' => $calc->getIntersect(), 'obj' => $calc, 'GOF' => $calc->getGoodnessOfFit(3) );
            
          case ($x_count == 4):

            // $trend = trendClass::TREND_POLYNOMIAL_3;
            // $type = "poly_3";
            // $calc = trendClass::calculate($trend, $y_vals, $x_vals);
            
            // $regressions[] = (object) array( 'type' => $type, 'equation' => $calc->getEquation(), 'slope' => $calc->getSlope(), 'intercept' => $calc->getIntersect(), 'obj' => $calc, 'GOF' => $calc->getGoodnessOfFit(3) );

          case ($x_count == 3):
 
            // $trend = trendClass::TREND_POLYNOMIAL_2;
            // $type = "poly_2";
            // $calc = trendClass::calculate($trend, $y_vals, $x_vals);
            
            // $regressions[] = (object) array( 'type' => $type, 'equation' => $calc->getEquation(), 'slope' => $calc->getSlope(), 'intercept' => $calc->getIntersect(), 'obj' => $calc, 'GOF' => $calc->getGoodnessOfFit(3) );

          case ($x_count == 2):

            $trend = trendClass::TREND_LINEAR;
            $type = "linear";
            $calc = trendClass::calculate($trend, $y_vals, $x_vals);
            
            $regressions[] = (object) array( 'type' => $type, 'equation' => $calc->getEquation(), 'slope' => $calc->getSlope(), 'intercept' => $calc->getIntersect(), 'obj' => $calc, 'GOF' => $calc->getGoodnessOfFit(3) );
            
            break;
          
          default:
            return array();
        }

        $best_reg = null;

        // Which is the best?
        foreach($regressions as $regression)
        {
          if(is_null($best_reg))
            $best_reg = $regression;

          if($regression->GOF > $best_reg->GOF)
            $best_reg = $regression;
        }

        // return array( 'type' => $type, 'equation' => $calc->getEquation(), 'slope' => $calc->getSlope(), 'intercept' => $calc->getIntersect(), 'obj' => $calc );
        return array( 'regressions' => $regressions, 'best' => $best_reg );

    }


}


?>