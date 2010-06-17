/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

package org.ulteo.ovd.client.portal;

import java.awt.Dimension;
import java.awt.Image;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ulteo.ovd.client.I18n;
import org.ulteo.rdp.RdpActions;


public class PortalFrame extends JFrame implements WindowListener{

	private Image logo = null;
	private MainPanel main = null;
	private RdpActions rdpActions = null;
	
	public PortalFrame() {
		logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		main = new MainPanel();
		setIconImage(logo);
		this.setVisible(false);
		this.setTitle("Portal");
		this.setSize(800, 600);
		this.setPreferredSize(new Dimension(800,600));
		this.setResizable(false);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.addWindowListener(this);
		this.setContentPane(main);
		this.setLocationRelativeTo(null);
		this.pack();
	}

	public MainPanel getMain() {
		return main;
	}



	public void setMain(MainPanel main) {
		this.main = main;
	}

	public void initButtonPan(RdpActions rdpActions_) {
		this.rdpActions = rdpActions_;
		this.main.getSouth().initButtonPan(this.rdpActions, this.main.getCenter().getCurrent());
	}

	@Override
	public void windowActivated(WindowEvent arg0) {}

	@Override
	public void windowClosed(WindowEvent arg0) {}

	@Override
	public void windowClosing(WindowEvent arg0) {
		if (this.rdpActions != null) {
			int option = JOptionPane.showConfirmDialog(null, I18n._("Do you really want to close the window ?"), I18n._("Warning !"), JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);

			if(option == JOptionPane.OK_OPTION) {
				this.rdpActions.exit(0);
			}
			else {
				this.setVisible(true);
			}
		}
	}

	@Override
	public void windowDeactivated(WindowEvent arg0) {}

	@Override
	public void windowDeiconified(WindowEvent arg0) {}

	@Override
	public void windowIconified(WindowEvent arg0) {}

	@Override
	public void windowOpened(WindowEvent arg0) {}
}
