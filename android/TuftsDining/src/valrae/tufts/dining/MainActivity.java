package valrae.tufts.dining;

import valrae.tufts.dining.VenueDialogFragment.VenueDialogListener;
import android.app.Activity;
import android.content.res.Configuration;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.support.v4.app.FragmentManager;
import android.support.v4.app.FragmentTransaction;
import android.support.v4.widget.DrawerLayout;
import android.support.v7.app.ActionBar;
import android.support.v7.app.ActionBarActivity;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;

// TODO long term: once a day check for new venues/foods/vote types etc. >> save this in shared preferences

// TODO, create call back to check is drawer is open for options menu.


public class MainActivity extends ActionBarActivity
        implements NavigationDrawerFragment.NavigationDrawerCallbacks, VenueDialogListener {
	
    /**
     * Fragment managing the behaviors, interactions and presentation of the navigation drawer.
     */
    private NavigationDrawerFragment mNavigationDrawerFragment;
    private RatingFragment mRatingFragment;
    private static int sectionNum;

    /**
     * Used to store the last screen title. For use in {@link #restoreActionBar()}.
     */
    private CharSequence mTitle;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        mNavigationDrawerFragment = (NavigationDrawerFragment)
                getSupportFragmentManager().findFragmentById(R.id.navigation_drawer);
        mTitle = getTitle();

        // Set up the drawer.
        mNavigationDrawerFragment.setUp(R.id.navigation_drawer,
                (DrawerLayout) findViewById(R.id.drawer_layout));
    }

    @Override
    public void onNavigationDrawerItemSelected(int position) {
        sectionNum = position;
    	
        // update the main content by replacing fragments
        FragmentManager fragmentManager = getSupportFragmentManager();
        FragmentTransaction fTrans = fragmentManager.beginTransaction();

        switch (sectionNum) {
        case 0:		// rating
        	mRatingFragment = RatingFragment.newInstance(this);
        	fTrans.replace(R.id.container, mRatingFragment)
        	.commit();
            break;
        case 1:		// where to eat? 
//        	mWhereToFragment = WhereToFragment.newInstance(this);	// TODO
//        	fTrans.replace(R.id.container, mWhereToFragment)
//        	.commit();
//        	fTrans.replace(R.id.container, PlaceholderFragment.newInstance(position))
//    			  .commit();
        	break;
    	default:	// default >> PlaceHolderFragment
    		fTrans.replace(R.id.container, PlaceholderFragment.newInstance(position))
    			  .commit();
        	break;
    	}
        
    }

    public void onSectionAttached(int number) {
        switch (number) {
            case 1:
            	mTitle = getString(R.string.title_rating);
                break;
            case 2:
                mTitle = getString(R.string.title_where_to);
                break;
        }
    }

    public void restoreActionBar() {
        ActionBar actionBar = getSupportActionBar();
        actionBar.setNavigationMode(ActionBar.NAVIGATION_MODE_STANDARD);
        actionBar.setDisplayShowTitleEnabled(true);
        actionBar.setTitle(mTitle);
    }


    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        if (!mNavigationDrawerFragment.isDrawerOpen()) {
            // Only show items in the action bar relevant to this screen
            // if the drawer is not showing. Otherwise, let the drawer
            // decide what to show in the action bar.
            getMenuInflater().inflate(R.menu.main, menu);
            restoreActionBar();
            return true;
        }
        return super.onCreateOptionsMenu(menu);
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle action bar item clicks here. The action bar will
        // automatically handle clicks on the Home/Up button, so long
        // as you specify a parent activity in AndroidManifest.xml.
        int id = item.getItemId();
        if (id == R.id.action_settings) {
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    /** 
	 */
	@Override
	public void onConfigurationChanged(Configuration newConfig) {
		// called by configuration changes listed in manifest
		super.onConfigurationChanged(newConfig);
	}

	/**
     * A placeholder fragment containing a simple view.
     */
    public static class PlaceholderFragment extends Fragment {
        /**
         * The fragment argument representing the section number for this
         * fragment.
         */
        private static final String ARG_SECTION_NUMBER = "section_number";

        /**
         * Returns a new instance of this fragment for the given section
         * number.
         */
        public static PlaceholderFragment newInstance(int sectionNumber) {
            PlaceholderFragment fragment = new PlaceholderFragment();
            Bundle args = new Bundle();
            args.putInt(ARG_SECTION_NUMBER, sectionNumber);
            fragment.setArguments(args);
            return fragment;
        }

        public PlaceholderFragment() {
        }

        @Override
        public View onCreateView(LayoutInflater inflater, ViewGroup container,
                Bundle savedInstanceState) {
        	View rootView = inflater.inflate(R.layout.fragment_main, container, false);
            return rootView;
        }

        @Override
        public void onAttach(Activity activity) {
            super.onAttach(activity);
            ((MainActivity) activity).onSectionAttached(
            		getArguments().getInt(ARG_SECTION_NUMBER));
        }
    }
    
/* ------------------------- Button methods ------------------------- */
    /**
     * Called by the rating fragment's buttons
     * @param button
     */
    public void onClickRating (View button) {
    	mRatingFragment.onClick(button);
	}
    
    /* ------------------------- Callback methods ------------------------- */

    // pass these calls along to RatingFragment
	@Override
	public void onSelectionMade(int which) {
		mRatingFragment.onSelectionMade(which);
		
	}

	@Override
	public void onDialogNegativeClick() {
		mRatingFragment.onDialogNegativeClick();
	}
    
}
